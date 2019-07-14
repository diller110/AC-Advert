"use strict";
let page = window.location.pathname.match('/([a-z]*)$')[1];

let modal2 = document.querySelector('.modal2');
if(modal2) {
  modal2.querySelector('.modal2-close').onclick = function(e) {
    modal2.classList.remove('active');
  };
  for(let elem of modal2.querySelectorAll('[href="#close"]')) {
    elem.onclick = function(e) {
      modal2.classList.remove('active');
    };
  }
}
let btn_add = document.querySelector('a[href$="#btn_add"]');
if(btn_add) btn_add.onclick = function(e) {
  modal2.classList.add('active');
  switch (page) {
    case "":
      modal2.querySelector("#title").innerHTML = modal2.querySelector("#title").getAttribute('lang1')+":";
      modal2.querySelector("button[type=submit]").innerHTML = modal2.querySelector("button[type=submit]").getAttribute('lang1');
      modal2.querySelector('#in_srv_id').value = -1;
      modal2.querySelector('#in_title').value = "";
      modal2.querySelector('#in_ip').value = "";
      modal2.querySelector('#in_port').value = "";
      modal2.querySelector('#in_rcon').value = "";
      modal2.querySelector('#in_adv_time').value = "";
      break;
    case 'words':
      modal2.querySelector("#title").innerHTML = modal2.querySelector("#title").getAttribute('lang1')+":";
      modal2.querySelector("button[type=submit]").innerHTML = modal2.querySelector("button[type=submit]").getAttribute('lang1');
      modal2.querySelector('#in_word_id').value = -1;
      modal2.querySelector('#in_key').value = "";
      modal2.querySelector('#in_value').value = "";
      break;
    case 'ads':
      modal2.querySelector("#title").innerHTML = modal2.querySelector("#title").getAttribute('lang1')+":";
      modal2.querySelector("button[type=submit]").innerHTML = modal2.querySelector("button[type=submit]").getAttribute('lang1');
      modal2.querySelector('#in_ads_id').value = -1;
      modal2.querySelector('#in_show').checked = true;
      modal2.querySelector('#in_srv_id').value = null;
      modal2.querySelector('#in_msg_type').value = 1;
      modal2.querySelector('#in_msg_text').value = null;
      modal2.querySelector('#in_date_from').value = null;
      modal2.querySelector('#in_date_to').value = null;
      modal2.querySelector('#in_hours').value = null;
      modal2.querySelector('#in_day_of_week').value = null;
      modal2.querySelector('#in_is_vip').value = "0";
      modal2.querySelector('#in_admin_flags').value = null;
      modal2.querySelector('#in_views').value = null;
      modal2.querySelector('#in_order').value = null;

      modal2.querySelector('#in_hud_color1').value = null;
      modal2.querySelector('#in_hud_color2').value = null;
      modal2.querySelector('#in_hud_effect').value = null;
      modal2.querySelector('#in_hud_fadein').value = null;
      modal2.querySelector('#in_hud_fadeout').value = null;
      modal2.querySelector('#in_hud_holdtime').value = null;
      modal2.querySelector('#in_hud_fxtime').value = null;
      modal2.querySelector('#in_hud_x').value = null;
      modal2.querySelector('#in_hud_y').value = null;
      if(modal2.querySelector('#in_msg_type').value == 2) {
        document.querySelector("#group_hud").style.display = 'flex';
      } else {
        document.querySelector("#group_hud").style.display = 'none';
      }
      break;
    default:
      break;
  }
  return false;
};
for(let elem of document.querySelectorAll('a[href$="#btn_edit"]')) {
  elem.onclick = function(e) {
    (async () => {
      let response;
      switch (page) {
        case "":
          response = await fetch('./ex/server/'+e.target.id);
          if (response.ok) {
            modal2.classList.add('active');
            let json = await response.json();
            modal2.querySelector("#title").innerHTML = modal2.querySelector("#title").getAttribute('lang2')+":";
            modal2.querySelector("button[type=submit]").innerHTML = modal2.querySelector("button[type=submit]").getAttribute('lang2');
            modal2.querySelector('#in_srv_id').value = json.srv_id;
            modal2.querySelector('#in_title').value = json.title;
            modal2.querySelector('#in_ip').value = json.ip;
            modal2.querySelector('#in_port').value = json.port;
            modal2.querySelector('#in_rcon').value = json.rcon;
            modal2.querySelector('#in_adv_time').value = json.adv_time;
          } else {
            console.log("HTTP-Error: " + response.status);
          }
          break;
        case 'words':
          response = await fetch('./ex/words/'+e.target.id);
          if (response.ok) {
            modal2.classList.add('active');
            let json = await response.json();
            modal2.querySelector("#title").innerHTML = modal2.querySelector("#title").getAttribute('lang2')+":";
            modal2.querySelector("button[type=submit]").innerHTML = modal2.querySelector("button[type=submit]").getAttribute('lang2');
            modal2.querySelector('#in_word_id').value = json.word_id;
            modal2.querySelector('#in_key').value = json.key;
            modal2.querySelector('#in_value').value = json.value;
          } else {
            console.log("HTTP-Error: " + response.status);
          }
          break;
        case 'ads':
          response = await fetch('./ex/ads/'+event.target.id);
          if (response.ok) {
            modal2.classList.add('active');
            let json = await response.json();
            modal2.querySelector("#title").innerHTML = modal2.querySelector("#title").getAttribute('lang2')+":";
            modal2.querySelector("button[type=submit]").innerHTML = modal2.querySelector("button[type=submit]").getAttribute('lang2');

            modal2.querySelector('#in_ads_id').value = json.adv_id;
            modal2.querySelector('#in_show').checked = json.show;
            for(let elem2 of document.querySelectorAll("#in_srv_id option")) {
              if(json.servers.includes(elem2.value)) {
                elem2.selected = true;
              }
            }
            modal2.querySelector('#in_msg_type').value = json.msg_type;
            modal2.querySelector('#in_msg_text').value = json.msg_text;
            modal2.querySelector('#in_date_from').value = json.date_from;
            modal2.querySelector('#in_date_to').value = json.date_to;
            modal2.querySelector('#in_hours').value = json.hours;
            modal2.querySelector('#in_day_of_week').value = json.day_of_week;
            modal2.querySelector('#in_is_vip').value = json.is_vip;
            modal2.querySelector('#in_admin_flags').value = json.admin_flags;
            modal2.querySelector('#in_views').value = json.views;
            modal2.querySelector('#in_order').value = json.order;

            if(json.hud) {
              modal2.querySelector('#in_hud_color1').value = json.hud['color1'];
              modal2.querySelector('#in_hud_color2').value = json.hud['color2'];
              modal2.querySelector('#in_hud_effect').value = json.hud['effect'];
              modal2.querySelector('#in_hud_fadein').value = json.hud['fadein'];
              modal2.querySelector('#in_hud_fadeout').value = json.hud['fadeout'];
              modal2.querySelector('#in_hud_holdtime').value = json.hud['holdtime'];
              modal2.querySelector('#in_hud_fxtime').value = json.hud['fxtime'];
              modal2.querySelector('#in_hud_x').value = json.hud['x'];
              modal2.querySelector('#in_hud_y').value = json.hud['y'];
            }

            if(modal2.querySelector('#in_msg_type').value == 2) {
              document.querySelector("#group_hud").style.display = 'flex';
            } else {
              document.querySelector("#group_hud").style.display = 'none';
            }
          } else {
            console.log("HTTP-Error: " + response.status);
          }
          break;
        default:
          break;
      }
    })();
    return false;
  };
}

let in_filter = document.querySelector("#in_filter");
if(in_filter) in_filter.oninput = function(event) {
  let tbody = document.querySelector("tbody#items_list");
  if(tbody) {
    for(let elem of tbody.querySelectorAll("tr")) {
      let td = elem.querySelectorAll('td');
      let show = false;
      for(let i = 0; i<td.length-1; i++) {
        if(td[i].textContent.toLowerCase().includes(event.target.value.toLowerCase())) {
          show = true;
        }
      }
      elem.style.display = show?'table-row':'none';
    }
  }
};

function colorize(elem2) {
  elem2.innerHTML = elem2.innerHTML.replace(/{\\01}/g, '<span style="color:white;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\02}/g, '<span style="color:#ff0000;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\03}/g, '<span style="color:#ba81f0;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\04}/g, '<span style="color:#40ff40;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\05}/g, '<span style="color:#bfff90;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\06}/g, '<span style="color:#a2ff47;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\07}/g, '<span style="color:#ff4040;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\08}/g, '<span style="color:#c5cad0;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\09}/g, '<span style="color:#ede47a;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\10}/g, '<span style="color:#b0c3d9;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\11}/g, '<span style="color:#5e98d9;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\12}/g, '<span style="color:#4b69ff;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\13}/g, '<span style="color:#b0c3d9;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\14}/g, '<span style="color:#d32ce6;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\15}/g, '<span style="color:#eb4b4b;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\16}/g, '<span style="color:#e4ae39;">');
  elem2.innerHTML = elem2.innerHTML.replace(/{\\ni/g, '{\\<span>ni');
  elem2.innerHTML = elem2.innerHTML.replace(/\\n/g, '<br>');
  elem2.innerHTML += '</span>';
}
window.onload = function(e) {
  for(let elem of document.querySelectorAll('.colorize')) {
    colorize(elem);
  }
};
