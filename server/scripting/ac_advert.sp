#pragma semicolon 1
#pragma newdecls required

//#include <aclib>
#include <dynamic>
#include <regex>
#include <cstrike>

#define STRLEN 1024

Database db;
Dynamic words, ads;
char title_db[128], address[20];
int port = 0, srv_id = -1, cur = 1;
float sec = 45.0;

//{word}	Value
//{/ip}		192.168.0.1
//{\1}		\x01
//PrintToChatAll(" \x01█{\\01}█\x02█{\\02}█\x03█{\\03}█\x04█{\\04}█\x05█{\\05}█\x06█{\\06}█");
//PrintToChatAll(" \x07█{\\07}█\x08█{\\08}█\x09█{\\09}█\x0A█{\\10}█\x0B█{\\11}█\x0C█{\\12}█");
//PrintToChatAll(" \x0D█{\\13}█\x0E█{\\14}█\x0F█{\\15}█\x10█{\\16}█");

public Plugin myinfo = {
	name = "AC: Advertisement",	author = "diller110",
	description = "MySQL-based advert", version = "1.0a"
};

public void OnPluginStart() {
	words = Dynamic();
	ads = Dynamic();
	
	FindConVar("ip").GetString(address, sizeof(address));
	port = FindConVar("hostport").IntValue;
	
	Database.Connect(SqlConnect, "ac_advert");
	
	RegServerCmd("sm_ac_adv_update", SrvCmd_Update);
}
public void OnPluginEnd() {
	if (words.IsValid)words.Dispose();
	if (ads.IsValid)ads.Dispose();
}
public Action SrvCmd_Update(int args) {
	PrintToServer("[AC:Advert] Get Update command from web.");
	
	char buff[256];
	GetPluginFilename(null, buff, sizeof(buff));
	ServerCommand("sm plugins reload %s", buff);
	
	return Plugin_Handled;
}

void SqlConnect(Database dbl, const char[] error, any data) {
	if(dbl) {
		db = dbl;
		SQL_FastQuery(db, "SET NAMES \"UTF8\""); 
		char query[128];
		Format(query, sizeof(query), "SELECT * FROM `servers` WHERE ip='%s' and port='%d'", address, port);
		db.Query(SqlQueryServers, query);
	} else {
		SetFailState("Failed to connect database. Check 'ac_advert' database configuration.");
	}
}
void SqlQueryServers(Database dbl, DBResultSet results, const char[] error, any data) {
	if(results == null) {
		SetFailState("Fail on sql query: Servers list");
		return;
	}
	
	if(results.RowCount == 0) {
		SetFailState("This server not registered in database. Check: %s:%d", address, port);
		return;
	}
	results.FetchRow();
	
	srv_id = results.FetchInt(0);
	sec = results.FetchFloat(5);
	results.FetchString(3, title_db, sizeof(title_db));
	PrintToServer("[AC:Advert] Server logged as '%s'(%d). Timer - %.1f sec.", title_db, srv_id, sec);
	
	db.Query(SqlQueryWords, "SELECT * FROM `magic_words`");
}
void SqlQueryWords(Database dbl, DBResultSet results, const char[] error, any data) {
	if(results == null) {
		SetFailState("Fail on sql query: Words list");
		return;
	}
	
	char key[48];
	char value[512];
	
	if(results.RowCount == 0) {
		PrintToServer("[AC:Advert] Magic words from db not found. It's strange.");
	} else {
		while(results.FetchRow()) {
			results.FetchString(1, key, sizeof(key));
			results.FetchString(2, value, sizeof(value));
			//PrintToServer("[AC:Advert] Magic word: '%s' loaded.", key);
			words.SetString(key, value);
		}
		
		PrintToServer("[AC:Advert] Loaded %d magic words. %s", words.MemberCount, words.MemberCount>50?"It's very big number, processing will cause freezes...":"");
	}
	
	Format(value, sizeof(value), "SELECT adv_id, msg_type, UNIX_TIMESTAMP(date_from) as date_from, UNIX_TIMESTAMP(date_to) as date_to, is_vip, views, msg_text, hours, admin_flags, day_of_week, color1, color2, effect, fadein, fadeout, holdtime, x, y, fxtime from ads WHERE srv_id=%d AND ((NOW()>=date_from OR date_from = 0) AND (NOW()<=date_to OR date_to = 0))", srv_id);
	db.Query(SqlQueryAds, value);
}
void SqlQueryAds(Database dbl, DBResultSet results, const char[] error, any data) {
	if(results == null) {
		SetFailState("Fail on sql query: Ads list");
		return;
	}
	
	
	if(results.RowCount == 0) {
		PrintToServer("[AC:Advert] No advertisements found for this server.");
	} else {
		char buff[STRLEN];
		int count = 1;
		while(results.FetchRow()) {
			Dynamic adv = Dynamic();
			
			adv.SetInt("adv_id", results.FetchInt(0));
			adv.SetInt("msg_type", results.FetchInt(1));
			adv.SetInt("date_from", results.FetchInt(2));
			adv.SetInt("date_to", results.FetchInt(3));
			adv.SetBool("is_vip", view_as<bool>(results.FetchInt(4)));	
			adv.SetInt("views", results.FetchInt(5));
			results.FetchString(6, buff, STRLEN);
			//PrintToServer("[AC:Advert] Input: %s", buff);
			FormatCustom(buff);
			FormatColors(buff);
			if(StrContains(buff, "{/") != -1) {
				adv.SetBool("changeable", true);
			}
			if(StrContains(buff, "{\\") != -1) {
				adv.SetBool("userable", true);
			}
			//PrintToServer("[AC:Advert] Output: %s", buff);
			adv.SetString("msg_text", buff);
			results.FetchString(7, buff, 64);
			adv.SetString("hours", buff);
			results.FetchString(8, buff, 64);
			adv.SetString("admin_flags", buff);
			results.FetchString(9, buff, 64);
			adv.SetString("day_of_week", buff);
			
			if(adv.GetInt("msg_type") == 2) {
				results.FetchString(10, buff, 64);
				adv.SetString("color1", buff);
				results.FetchString(11, buff, 64);
				adv.SetString("color2", buff);
				adv.SetInt("effect", results.FetchInt(12));
				adv.SetFloat("fadein", results.FetchFloat(13));
				adv.SetFloat("fadeout", results.FetchFloat(14));
				adv.SetFloat("holdtime", results.FetchFloat(15));
				adv.SetFloat("x", results.FetchFloat(16));
				adv.SetFloat("y", results.FetchFloat(17));
				adv.SetFloat("fxtime", results.FetchFloat(18));
			}
			
			Format(buff, 6, "%d", count++);
			ads.SetDynamic(buff, adv);
		}
		
		PrintToServer("[AC:Advert] Loaded %d ads. Main timer starts now.", ads.MemberCount);
		CreateTimer(sec, Timer_Main, _, TIMER_REPEAT);
	}
}
public Action Timer_Main(Handle timer) {
	if(!ads.IsValid) {
		SetFailState("[AC:Advert] Error: Invalid ads object in main timer.");
	}
	static char buff[STRLEN], buff_u[STRLEN];
	Format(buff, 6, "%d", cur++);
	Dynamic adv = ads.GetDynamic(buff);
	if(!adv.IsValid) {
		SetFailState("[AC:Advert] Error: Invalid adv object in main timer.");
	}
	if(cur>ads.MemberCount) {
		cur = 1;
	}
	

	adv.GetString("hours", buff, 64);
	if(!CheckHours(buff)) {
		return;
	}
	adv.GetString("day_of_week", buff, 64);
	if(!CheckDays(buff)) {
		return;
	}
	adv.GetString("msg_text", buff, STRLEN);
	if(adv.GetBool("changeable", false)) {
		FormatChangeable(buff);
	}
	bool userable = adv.GetBool("userable");
	
	static char buffs[6][STRLEN], buffs_u[6][STRLEN];
	switch(adv.GetInt("msg_type")) {
		case 1: {
			
			buffs[0][0] = buffs[1][0] = buffs[2][0] = buffs[3][0] = buffs[4][0] = '\0';
			ExplodeString(buff, "\\n", buffs, sizeof(buffs), sizeof(buffs[]));
			
			for (int i = 1; i < MaxClients; i++) {
				if(!IsClientInGame(i) || IsFakeClient(i)) {
					continue;
				}
				for (int i2 = 0; i2 < 5; i2++) {
					if(userable) {
						strcopy(buffs_u[i2], sizeof(buffs_u[]), buffs[i2]);
						FormatUserable(buffs_u[i2], i);
						PrintToChat(i, buffs_u[i2]);
					} else {
						PrintToChat(i, buffs[i2]);
					}
				}
			}
		}
		case 2: {
			int color1[4], color2[4];
			adv.GetString("color1", buffs[0], sizeof(buffs[]));
			StrToVec4(buffs[0], color1);
			adv.GetString("color2", buffs[0], sizeof(buffs[]));
			ExplodeString(buffs[0], " ", buffs, sizeof(buffs), sizeof(buffs[]));
			color2[0] = StringToInt(buffs[0]); color2[1] = StringToInt(buffs[1]);
			color2[2] = StringToInt(buffs[2]); color2[3] = StringToInt(buffs[3]);
			ReplaceString(buff, sizeof(buff), "\\n", "\n");
			
			SetHudTextParamsEx(adv.GetFloat("x", -1.0), adv.GetFloat("y", -1.0), adv.GetFloat("holdtime", 5.0), color1, color2, adv.GetInt("effect"), adv.GetFloat("fxtime"), adv.GetFloat("fadein"), adv.GetFloat("fadeout"));
			for (int i = 1; i < MaxClients; i++) {
				if(!IsClientInGame(i) || IsFakeClient(i)) {
					continue;
				}
				if(userable) {
					strcopy(buff_u, sizeof(buff_u), buff);
					FormatUserable(buff_u, i);
					ShowHudText(i, -1, buff_u);
				} else {
					ShowHudText(i, -1, buff);
				}				
			} 
		}
	}

}
void StrToVec4(char []str, int vec4[4]) {
	char buff[4][6];
	ExplodeString(str, " ", buff, sizeof(buff), sizeof(buff[]));
	vec4[0] = StringToInt(buff[0]); vec4[1] = StringToInt(buff[1]);
	vec4[2] = StringToInt(buff[2]); vec4[3] = StringToInt(buff[3]);
}
bool CheckHours(char[] str) {
	Regex reg = new Regex("\\d*-*\\d*");
	if(reg == null) return false;
	
	char key[8];
	char hour[4];
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
	Regex reg = new Regex("[1-7]*-*[1-7]*");
	if(reg == null) return false;
	
	char key[8];
	char day[4];
	FormatTime(day, sizeof(day), "%u", GetTime());
	char buffs[2][4];
	
	if(reg.MatchAll(str) > 0) {
		for (int i = 0; i < reg.CaptureCount(); i++) {
			reg.GetSubString(0, key, sizeof(key), i);
			if(FindCharInString(key, '-') == -1) {
				if(StrEqual(key, day)) {
					return true;
				}
			} else {
				ExplodeString(key, "-", buffs, sizeof(buffs), sizeof(buffs[]));
				if(StringToInt(buffs[0]) <= StringToInt(day) <= StringToInt(buffs[1])) {
					return true;
				}
			}
		}
	}
	
	return false;
}
void FormatCustom(char str[STRLEN]) {
	static Regex reg = null;
	if(reg == null) reg = new Regex("{([A-Za-z0-9]+)}");
	if(reg == null) return;
	char key[64];
	char value[256];
	if(reg.MatchAll(str) > 0) {
		for (int i = 0; i < reg.MatchCount(); i++) {
			value[0] = 0;
			reg.GetSubString(1, key, sizeof(key), i);
			words.GetString(key, value, sizeof(value));
			Format(key, sizeof(key), "{%s}", key);
			if(value[0]) {
				ReplaceString(str, sizeof(str), key, value);
			} else {
				Format(value, sizeof(value), "{-%s}", key);
				ReplaceString(str, sizeof(str), key, value);
			}
		}
	}
	
	int c = FindCharInString(str, '{');
	if(c != -1 && str[c+1] == '-') {
		FormatCustom(str);
	}
}
void FormatColors(char str[STRLEN]) {
	Regex reg = new Regex("{\\\\([0-1]{1}[0-9]{1})}");
	if(reg == null) {
		return;
	}
	int c = 1;
	char key[6];
	char value[6];
	if(reg.MatchAll(str) > 0) {
		for (int i = 0; i < reg.MatchCount(); i++) {
			value[0] = 0;
			reg.GetSubString(1, key, sizeof(key), i);
			c = StringToInt(key);
			Format(key, sizeof(key), "{\\%s}", key);
			Format(value, sizeof(value), "%s%c", i==0?" ":"", c);
			ReplaceString(str, sizeof(str), key, value);
		}
	}
}
void FormatChangeable(char str[STRLEN]) {
	if (StrContains(str, "{/") == -1) return;
	
	char buff[64];
	if(StrContains(str, "{/time}") != -1) {
		FormatTime(buff, 16, "%X", GetTime());
		ReplaceString(str, sizeof(str), "{/time}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;

	if(StrContains(str, "{/date}") != -1) {
		FormatTime(buff, 16, "%d.%m.%Y", GetTime());
		ReplaceString(str, sizeof(str), "{/date}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/addr}") != -1) {
		ReplaceString(str, sizeof(str), "{/addr}", address);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/port}") != -1) {
		Format(buff, 10, "%d", port);
		ReplaceString(str, 10, "{/port}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/hostname}") != -1) {
		FindConVar("hostname").GetString(buff, sizeof(buff));
		ReplaceString(str, 10, "{/hostname}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/hostname_db}") != -1) {
		ReplaceString(str, 10, "{/hostname_db}", title_db);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/slots}") != -1) {
		Format(buff, 10, "%d", GetMaxHumanPlayers());
		ReplaceString(str, 10, "{/slots}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/players}") != -1) {
		Format(buff, 10, "%d", GetClientCount());
		ReplaceString(str, 10, "{/players}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/map}") != -1) {
		GetCurrentMap(buff, sizeof(buff));
		int c = FindCharInString(buff, '/', true);
		c = (c == -1) ? 0:c + 1;
		ReplaceString(str, 10, "{/map}", buff[c]);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/nextmap}") != -1) {
		GetNextMap(buff, sizeof(buff));
		int c = FindCharInString(buff, '/', true);
		c = (c == -1) ? 0:c + 1;
		ReplaceString(str, 10, "{/nextmap}", buff[c]);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/tickrate}") != -1) {
		Format(buff, 6, "%d", RoundToNearest(1.0 / GetTickInterval()));
		ReplaceString(str, 10, "{/tickrate}", buff);
	}
	
	if (StrContains(str, "{/") == -1) return;
	
	if(StrContains(str, "{/timeleft}") != -1) {
		int time;
		if(GetMapTimeLeft(time)) {
			Format(buff, sizeof(buff), "%d:%02d", time / 60, time % 60);
		}
		ReplaceString(str, 10, "{/timeleft}", buff);
	}
}
void FormatUserable(char str[STRLEN], int client) {
	if (StrContains(str, "{\\") == -1) return;
	char buff[64];
	if(StrContains(str, "{\nick}") != -1) {
		if(GetClientName(client, buff, sizeof(buff))) {
			ReplaceString(str, sizeof(str), "{\nick}", buff);
		} else {
			ReplaceString(str, sizeof(str), "{\nick}", "");
		}
	}
	if (StrContains(str, "{\\") == -1) return;
	if(StrContains(str, "{\\steamid}") != -1) {
		if(GetClientAuthId(client, AuthId_Steam2, buff, sizeof(buff))) {
			ReplaceString(str, sizeof(str), "{\\steamid}", buff);
		} else {
			ReplaceString(str, sizeof(str), "{\\steamid}", "");
		}
	}
	if (StrContains(str, "{\\") == -1) return;
	if(StrContains(str, "{\\steamid64}") != -1) {
		if(GetClientAuthId(client, AuthId_SteamID64, buff, sizeof(buff))) {
			ReplaceString(str, sizeof(str), "{\\steamid64}", buff);
		} else {
			ReplaceString(str, sizeof(str), "{\\steamid64}", "");
		}
	}
}