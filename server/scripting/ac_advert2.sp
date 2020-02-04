#undef REQUIRE_PLUGIN
#include <vip_core>
#define REQUIRE_PLUGIN

#pragma semicolon 1
#pragma newdecls required

#include <ripext>
#include <regex>

#define PLUGIN_VERSION "2.0a"

public Plugin myinfo = {
	name = "AC: Advert v2",	author = "diller110",
	description = "Web-based advert", version = PLUGIN_VERSION
};

bool authorized = false;
int adv_time = 45;
bool useVip = false;
HTTPClient httpClient;
ConVar cvToken = null, cvProvider = null;
char provider[64], token[32];
JSONArray ads = null;
int current = 0;
Handle mainTimer = null;

public void OnPluginStart() {	
	cvProvider = CreateConVar("sm_adv_provider", "", "Url of data provider");
	cvToken = CreateConVar("sm_adv_token", "", "Account token");
	
	RegServerCmd("sm_adv_update", Cmd_AdvUpdate);
}
public void OnAllPluginsLoaded() {
	useVip = LibraryExists("vip_core");
}
public void OnLibraryAdded(const char[] name) {
	if(strcmp("vip_core", name) == 0) {
		useVip = true;
	}
}
public void OnLibraryRemoved(const char[] name) {
	if(strcmp("vip_core", name) == 0) {
		useVip = false;
	}	
}
public void OnMapStart() {
	if (httpClient == null || !authorized)return;
	LoadAdvert();
}
public void OnConfigsExecuted() {
	cvProvider.GetString(provider, sizeof(provider));
	cvToken.GetString(token, sizeof(token));
	
	if(StrEqual(provider, "") || StrEqual(token, "")) {
		SetFailState("[AC:Adv] Setup convars: sm_adv_provider and sm_adv_token.");
	}
	
	ReAuth();
}
public Action Cmd_AdvUpdate(int args) {
	PrintToServer("[AC:Adv] Update command received from %s.", provider);
	//if (httpClient == null || !authorized) return Plugin_Handled;
	ReAuth();
	return Plugin_Handled;
}
public void ReAuth() {
	if(httpClient != null) {
		authorized = false;
		delete httpClient;
	}
	httpClient = new HTTPClient(provider);
	httpClient.SetHeader("Authorization", token);
	char buff[8];
	Format(buff, 8, "%d", FindConVar("hostport").IntValue);
	httpClient.SetHeader("Serverport", buff);
	httpClient.SetHeader("Pluginver", PLUGIN_VERSION);
	
	httpClient.Get("auth", OnAuthReceived);
}
public void OnAuthReceived(HTTPResponse response, any value) {
	if (response.Status != HTTPStatus_OK) {
		PrintToServer("[AC:Adv] OnAuthReceived: Failed to retrieve. (%d)", response.Status);
		return;
	}
	if (response.Data == null) {
		PrintToServer("[AC:Adv] OnAuthReceived: Invalid JSON response.");
		return;
	}
	JSONObject data = view_as<JSONObject>(response.Data);
	if(data.HasKey("error")) {
		PrintToServer("[AC:Adv] OnAuthReceived: Have errors.");
		char error[128];
		data.GetString("error", error, sizeof(error));
		PrintToServer("[AC:Adv] %s", error);
		return;
	}
	adv_time = data.GetInt("time");
	
	char buff[196];
	data.GetString("title", buff, sizeof(buff));
	PrintToServer("[AC:Adv] Server authorized as '%s' with %d sec. timer!", buff, adv_time);
	if(mainTimer != null) {
		KillTimer(mainTimer);
	}
	mainTimer = CreateTimer(adv_time*1.0, Timer_Main, _, TIMER_REPEAT);
	
	if(data.HasKey("msg")) {
		data.GetString("msg", buff, sizeof(buff));
		PrintToServer("[AC:Adv] PROVIDER MESSAGE: %s", buff);
	}
	
	authorized = true;
	LoadAdvert();
}
public void LoadAdvert() {
	if (httpClient == null || !authorized)return;
	if (ads != null) {
		ads.Clear();
		delete ads;
	}
	current = 0;
	httpClient.Get("get", OnGetReceived);
}
public void OnGetReceived(HTTPResponse response, any value) {
	if (response.Status != HTTPStatus_OK) {
		PrintToServer("[AC:Adv] OnGetReceived: Failed to retrieve. (%d)", response.Status);
		return;
	}
	if (response.Data == null) {
		PrintToServer("[AC:Adv] OnGetReceived: Invalid JSON response.");
		return;
	}
	JSONObject data = view_as<JSONObject>(response.Data);
	if(data.HasKey("error")) {
		PrintToServer("[AC:Adv] OnGetReceived: Have errors.");
		char error[512];
		data.GetString("error", error, sizeof(error));
		PrintToServer("[AC:Adv] %s", error);
		return;
	}
	ads = view_as<JSONArray>(data.Get("ads"));
	PrintToServer("[AC:Adv] %d ads loaded!", ads.Length);
}
public Action Timer_Main(Handle timer) {
	if (ads == null) return Plugin_Continue;
	
	JSONObject adv = view_as<JSONObject>(ads.Get(current++));
	if(current >= ads.Length) current = 0;
	if (adv == null)SetFailState("[AC:Adv] Timer_Main: Invalid advert object.");
	PrintAdvert(adv);
	delete adv;
	return Plugin_Continue;
}
#define STRLEN 1024
void PrintAdvert(JSONObject adv) {
	static char buff[STRLEN], buff_u[STRLEN];
	buff[0] = 0;
	adv.GetString("hours", buff, 64);
	if(buff[0] && !CheckHours(buff)) {
		return;
	}
	adv.GetString("day_of_week", buff, 10);
	if(buff[0] && !CheckDays(buff)) {
		return;
	}
	adv.GetString("msg_text", buff, STRLEN);
	if(adv.GetBool("changeable")) {
		FormatChangeable(buff, strlen(buff));
	}
	bool userable = adv.GetBool("userable");
	
	static char buffs[7][STRLEN], buffs_u[7][STRLEN];
	switch(adv.GetInt("msg_type")) {
		case 0: {
			buffs[0][0] = buffs[1][0] = buffs[2][0] = buffs[3][0] = buffs[4][0] = buffs[5][0] = buffs[6][0] = '\0';
			ExplodeString(buff, "\\n", buffs, sizeof(buffs), sizeof(buffs[]));
			
			for (int i = 1; i < MaxClients; i++) {
				if(!IsClientInGame(i) || IsFakeClient(i)) continue;
				if (!CheckPlayer(adv, i))continue;
				for (int i2 = 0; i2 < sizeof(buffs); i2++) {
					if(!buffs[i2][0]) continue;
					if(userable) {
						strcopy(buffs_u[i2], sizeof(buffs_u[]), buffs[i2]);
						FormatUserable(buffs_u[i2], sizeof(buffs_u[]), i);
						PrintToChat(i, buffs_u[i2]);
					} else {
						PrintToChat(i, buffs[i2]);
					}
				}
			}
			
		}
		case 1: {
			JSONObject hud = view_as<JSONObject>(adv.Get("hud"));
			if(hud == null) {
				return;
			}		
			int color1[4], color2[4];
			hud.GetString("color1", buffs[0], sizeof(buffs[]));
			StrToVec4(buffs[0], color1);
			hud.GetString("color2", buffs[0], sizeof(buffs[]));
			StrToVec4(buffs[0], color2);
			ReplaceString(buff, sizeof(buff), "\\n", "\n");
			SetHudTextParamsEx(
				hud.GetFloat("x"), hud.GetFloat("y"),
				hud.GetFloat("holdtime"),
				color1, color2,
				hud.GetInt("effect"), hud.GetFloat("fxtime"),
				hud.GetFloat("fadein"), hud.GetFloat("fadeout")
			);
			delete hud;
			
			for (int i = 1; i < MaxClients; i++) {
				if(!IsClientInGame(i) || IsFakeClient(i)) continue;
				if (!CheckPlayer(adv, i)) continue;
				
				if(userable) {
					strcopy(buff_u, sizeof(buff_u), buff);
					FormatUserable(buff_u, sizeof(buff_u), i);
					ShowHudText(i, -1, buff_u);
				} else {
					ShowHudText(i, -1, buff);
				}				
			}
		}
		case 2: {
			ReplaceString(buff, sizeof(buff), "\\n", "\n");
			for (int i = 1; i < MaxClients; i++) {
				if(!IsClientInGame(i) || IsFakeClient(i)) continue;
				if (!CheckPlayer(adv, i))continue;
				if(userable) {
					strcopy(buff_u, sizeof(buff_u), buff);
					FormatUserable(buff_u, sizeof(buff_u), i);
					PrintHintText(i, buff_u);
				} else {
					PrintHintText(i, buff);
				}
			}
		}
		case 3: {
			ReplaceString(buff, sizeof(buff), "\\n", "\n");
			Menu m = CreateMenu(EmptyMenu_Handler);
			m.ExitButton = false;
			m.SetTitle(buff);
			m.AddItem("_", "Закрыть");
			for (int i = 1; i < MaxClients; i++) {
				if(!IsClientInGame(i) || IsFakeClient(i)) continue;
				if (!CheckPlayer(adv, i))continue;
				if(userable) {
					strcopy(buff_u, sizeof(buff_u), buff);
					FormatUserable(buff_u, sizeof(buff_u), i);
					m.SetTitle(buff_u);
				}
				m.Display(i, 20);
			}
			delete m;
		}
	}
}
void StrToVec4(char []str, int vec4[4]) {
	char buff[4][6];
	ExplodeString(str, " ", buff, sizeof(buff), sizeof(buff[]));
	vec4[0] = StringToInt(buff[0]); vec4[1] = StringToInt(buff[1]);
	vec4[2] = StringToInt(buff[2]); vec4[3] = StringToInt(buff[3]);
}
int EmptyMenu_Handler(Menu menu, MenuAction action, int client, int item) {

	return 0;
}
bool CheckPlayer(JSONObject adv, int client) {
	if(useVip) {
		int is_vip = adv.GetInt("is_vip");
		if(is_vip) {
			if(VIP_IsClientVIP(client)) {
				if (is_vip == -1)return false;
			} else {
				if (is_vip == 1) return false;
			}
		}
	}
	static char buff[16];
	buff[0] = 0;
	adv.GetString("admin_flags", buff, sizeof(buff));
	if (!buff[0])return true;
	
	AdminId adm = GetUserAdmin(client);
	if (adm == INVALID_ADMIN_ID)return false;
	
	int flags = ReadFlagString(buff);
	if(flags > 1) {
		AdminFlag flag;
		if(BitToFlag(flags, flag)) {
			if (!adm.HasFlag(flag, Access_Real)) return false;
		}
	}
	int im = FindCharInString(buff, ':');
	if(im != -1) {
		im = StringToInt(buff[im+1]);
		if(adm.ImmunityLevel < im) {
			return false;
		}
	}
	return true;
}
bool CheckHours(char[] str) {
	static Regex reg = null;
	if(reg == null) reg = new Regex("\\d*-*\\d*");
	if(reg == null) return false;
	
	static char key[8], hour[4];
	FormatTime(hour, sizeof(hour), "%H", GetTime());
	char buffs[2][4];
	
	if(reg.MatchAll(str) > 0) {
		for (int i = 0; i < reg.CaptureCount(); i++) {
			reg.GetSubString(0, key, sizeof(key), i);
			if(FindCharInString(key, '-') == -1) {
				if(StringToInt(hour) == StringToInt(key)) {
					return true;
				}
			} else {
				ExplodeString(key, "-", buffs, sizeof(buffs), sizeof(buffs[]));
				if(StringToInt(buffs[0]) <= StringToInt(hour) <= StringToInt(buffs[1])) {
					return true;
				}
			}
		}
	}
	return false;
}
bool CheckDays(char[] str) {
	char day[4];
	FormatTime(day, sizeof(day), "%u", GetTime());
	if(StrContains(str, day) == -1) {
		return false;
	}
	return true;
}
void FormatChangeable(char[] str, int length) {
	if (StrContains(str, "{/") == -1) return;
	
	static char buff[64];
	if(StrContains(str, "{/time}") != -1) {
		FormatTime(buff, 16, "%X", GetTime());
		ReplaceString(str, length, "{/time}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;

	if(StrContains(str, "{/date}") != -1) {
		FormatTime(buff, 16, "%d.%m.%Y", GetTime());
		ReplaceString(str, length, "{/date}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/hostname}") != -1) {
		FindConVar("hostname").GetString(buff, sizeof(buff));
		ReplaceString(str, length, "{/hostname}", buff);
	}
		
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/slots}") != -1) {
		Format(buff, 10, "%d", GetMaxHumanPlayers());
		ReplaceString(str, length, "{/slots}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/players}") != -1) {
		Format(buff, 10, "%d", GetClientCount());
		ReplaceString(str, length, "{/players}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/map}") != -1) {
		GetCurrentMap(buff, sizeof(buff));
		int c = FindCharInString(buff, '/', true);
		c = (c == -1) ? 0:c + 1;
		ReplaceString(str, length, "{/map}", buff[c]);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/nextmap}") != -1) {
		GetNextMap(buff, sizeof(buff));
		int c = FindCharInString(buff, '/', true);
		c = (c == -1) ? 0:c + 1;
		ReplaceString(str, length, "{/nextmap}", buff[c]);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/tickrate}") != -1) {
		Format(buff, 6, "%d", RoundToNearest(1.0 / GetTickInterval()));
		ReplaceString(str, length, "{/tickrate}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/timeleft}") != -1) {
		int time;
		if(GetMapTimeLeft(time)) {
			Format(buff, sizeof(buff), "%d:%02d", time / 60, time % 60);
		}
		ReplaceString(str, length, "{/timeleft}", buff);
	}
}

void FormatUserable(char[] str, int length, int client) {
	if (StrContains(str, "{\\") == -1) return;
	static char buff[64];
	if(StrContains(str, "{\\.nick}") != -1) {
		if(GetClientName(client, buff, sizeof(buff))) {
			ReplaceString(str, length, "{\\.nick}", buff);
		} else {
			ReplaceString(str, length, "{\\.nick}", "");
		}
	}
	if (StrContains(str, "{\\") == -1) return;
	if(StrContains(str, "{\\steamid}") != -1) {
		if(GetClientAuthId(client, AuthId_Steam2, buff, sizeof(buff))) {
			ReplaceString(str, length, "{\\steamid}", buff);
		} else {
			ReplaceString(str, length, "{\\steamid}", "");
		}
	}
	if (StrContains(str, "{\\") == -1) return;
	if(StrContains(str, "{\\steamid64}") != -1) {
		if(GetClientAuthId(client, AuthId_SteamID64, buff, sizeof(buff))) {
			ReplaceString(str, length, "{\\steamid64}", buff);
		} else {
			ReplaceString(str, length, "{\\steamid64}", "");
		}
	}
	if (StrContains(str, "{\\") == -1) return;
	if(useVip) {
		if(StrContains(str, "{\\vipGroup}") != -1) {
			if(!VIP_IsClientVIP(client) || !VIP_GetClientVIPGroup(client, buff, sizeof(buff))) {
				ReplaceString(str, length, "{\\vipGroup}", "-");
			} else {
				ReplaceString(str, length, "{\\vipGroup}", buff);
			}
		}
		if (StrContains(str, "{\\") == -1) return;
		if(StrContains(str, "{\\vipTime}") != -1) {
			if(!VIP_IsClientVIP(client) || !VIP_GetTimeFromStamp(buff, sizeof(buff), VIP_GetClientAccessTime(client)-GetTime(), client)) {
				ReplaceString(str, length, "{\\vipTime}", "-");
			} else {
				ReplaceString(str, length, "{\\vipTime}", buff);
			}
		}
	}
}