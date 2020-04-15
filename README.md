# AC:Advert v2 | Advertisements with WEB panel
Info: https://hlmod.ru/resources/ac-advert-reklamnye-soobschenija.1237/
#### Plugin requires: [sm-ripext](https://github.com/ErikMinekus/sm-ripext)


### Important info about v2
> Check: [Commit 5138ac8](https://github.com/diller110/AC-Advert/commit/5138ac852a7374e0dade729fe859af79e2eb037f) for v1
New plugin use provider as data source. (Check hlmod.ru for provider url)

Use convars in server.cfg:
```
sm_adv_provider "https://acboard.ru/dot/api"
sm_adv_token "YOUR_PERSONAL_TOKEN"
```
#### Convars:
* `sm_adv_provider "http://PROVIDER_URL/api"`  -  Url of data provider
* `sm_adv_token "YOUR_PERSONAL_TOKEN"`  -  Account token, find it on main provider's page after log in
* `sm_adv_allow_cmd "0"`  -  0/1, Enable ServerExecute from adv cmd, for update ads from web, or hot messages cmds.
* `sm_adv_force_ip ""`  -  "",1,"custom ip", Force set server ip (empty - default, 1 - hostip, or custom ip)

## Installation (self-hosted)
##### WEB:
1. Move /web/advert/... to your /path/...
2. Copy /path/app/config.example.ini to /path/app/config.ini
3. Create database/user execute SQL from: `/web/SQL запрос.txt`
4. Fill required settings in /path/app/config.ini: db credentials, and crypto_key (30 random characters, e.g. from https://passwordsgenerator.net/)
5. Register new account, add servers, words, ads, etc.
##### Server:
5. Install [sm-ripext](https://github.com/ErikMinekus/sm-ripext)
6. Add convars `sm_adv_provider`, `sm_adv_token` to server.cfg
7. Move /server/plugins/ac_advert.smx to /addons/sourcemod/plugins/...
8. Restart server or `sm plugins load ac_advert2`
