"use strict";
let modal_elem = document.getElementById('modal_main');
if(modal_elem) {
  for(let elem of document.querySelectorAll('a[href$="#close"]')) {
    elem.onclick = function(event) {
      modal_elem.classList.remove('active');
      return false;
    }
  }
}
let in_filter = document.querySelector("#in_filter");
if(in_filter) in_filter.oninput = function(event) {
  let tbody = document.querySelector("tbody#items_list");
  if(tbody) {
    for(let elem of tbody.querySelectorAll("tr")) {
      if(elem.textContent.toLowerCase().includes(event.target.value.toLowerCase())) {
        elem.style.visibility = null;
      } else {
        elem.style.visibility = "collapse";
      }
    }
  }
};
for(let elem of document.querySelectorAll(".confirm")) {
  elem.onclick = function(event) {
    var check = confirm("Вы уверены?");
    if(check) {
      return true;
    }
    return false;
  }
}

let btn_add_server = document.getElementById('btn_add_server');
if(btn_add_server) {
  btn_add_server.onclick = function(event) {
    modal_elem.classList.add('active');
    modal_elem.querySelector("#modal_title").innerHTML = "Новый сервер:";
    modal_elem.querySelector("#modal_submit").innerHTML = "Добавить";
    modal_elem.querySelector('#in_srv_id').value = -1;
    modal_elem.querySelector('#in_title').value = "";
    modal_elem.querySelector('#in_ip').value = "";
    modal_elem.querySelector('#in_port').value = "";
    modal_elem.querySelector('#in_rcon').value = "";
    modal_elem.querySelector('#in_adv_time').value = "";
  };

  for(let elem of document.querySelectorAll('a[href$="#srv_edit"]')) {
    elem.onclick = function (event){
      (async () => {
        let response = await fetch(window.location.origin+"/advert/ex/server/"+event.target.id);
        if (response.ok) {
          modal_elem.classList.add('active');
          let json = await response.json();
          modal_elem.querySelector("#modal_title").innerHTML = "Редактировать сервер:";
          modal_elem.querySelector("#modal_submit").innerHTML = "Изменить";
          modal_elem.querySelector('#in_srv_id').value = json.srv_id;
          modal_elem.querySelector('#in_title').value = json.title;
          modal_elem.querySelector('#in_ip').value = json.ip;
          modal_elem.querySelector('#in_port').value = json.port;
          modal_elem.querySelector('#in_rcon').value = json.rcon;
          modal_elem.querySelector('#in_adv_time').value = json.adv_time;
        } else {
          console.log("HTTP-Error: " + response.status);
        }
      })();
      return false;
    }
  }
}


let btn_add_word = document.getElementById('btn_add_word');
if(btn_add_word) {
  btn_add_word.onclick = function(event) {
    modal_elem.classList.add('active');
    modal_elem.querySelector("#modal_title").innerHTML = "Новое слово:";
    modal_elem.querySelector("#modal_submit").innerHTML = "Добавить";
    modal_elem.querySelector('#in_word_id').value = -1;
    modal_elem.querySelector('#in_key').value = "";
    modal_elem.querySelector('#in_value').value = "";
  };

  for(let elem of document.querySelectorAll('a[href$="#word_edit"]')) {
    elem.onclick = function (event){
      (async () => {
        let response = await fetch(window.location.origin+"/advert/ex/words/"+event.target.id);
        if (response.ok) {
          modal_elem.classList.add('active');
          let json = await response.json();
          modal_elem.querySelector("#modal_title").innerHTML = "Редактировать слово:";
          modal_elem.querySelector("#modal_submit").innerHTML = "Изменить";
          modal_elem.querySelector('#in_word_id').value = json.word_id;
          modal_elem.querySelector('#in_key').value = json.key;
          modal_elem.querySelector('#in_value').value = json.value;
        } else {
          console.log("HTTP-Error: " + response.status);
        }
      })();
      return false;
    }
  }
}


let btn_add_ads = document.getElementById('btn_add_ads');
if(btn_add_ads) {
  btn_add_ads.onclick = function(event) {
    modal_elem.classList.add('active');
    modal_elem.querySelector("#modal_title").innerHTML = "Новая реклама:";
    modal_elem.querySelector("#modal_submit").innerHTML = "Добавить";

    modal_elem.querySelector('#in_ads_id').value = -1;
    modal_elem.querySelector('#in_show').checked = true;
    modal_elem.querySelector('#in_srv_id').value = null;
    modal_elem.querySelector('#in_msg_type').value = 1;
    modal_elem.querySelector('#in_msg_text').value = null;
    modal_elem.querySelector('#in_date_from').value = null;
    modal_elem.querySelector('#in_date_to').value = null;
    modal_elem.querySelector('#in_hours').value = null;
    modal_elem.querySelector('#in_day_of_week').value = null;
    modal_elem.querySelector('#in_is_vip').value = null;
    modal_elem.querySelector('#in_admin_flags').value = null;
    modal_elem.querySelector('#in_views').value = null;
    modal_elem.querySelector('#in_order').value = null;

    modal_elem.querySelector('#in_hud_color1').value = null;
    modal_elem.querySelector('#in_hud_color2').value = null;
    modal_elem.querySelector('#in_hud_effect').value = null;
    modal_elem.querySelector('#in_hud_fadein').value = null;
    modal_elem.querySelector('#in_hud_fadeout').value = null;
    modal_elem.querySelector('#in_hud_holdtime').value = null;
    modal_elem.querySelector('#in_hud_fxtime').value = null;
    modal_elem.querySelector('#in_hud_x').value = null;
    modal_elem.querySelector('#in_hud_y').value = null;

    if(modal_elem.querySelector('#in_msg_type').value == 2) {
      document.querySelector("#group_hud").style.display = 'flex';
    } else {
      document.querySelector("#group_hud").style.display = 'none';
    }

  };

  for(let elem of document.querySelectorAll('a[href$="#ads_edit"]')) {
    elem.onclick = function (event){
      (async () => {
        let response = await fetch(window.location.origin+"/advert/ex/ads/"+event.target.id);
        if (response.ok) {
          let json = await response.json();
          modal_elem.querySelector("#modal_title").innerHTML = "Редактировать рекламу:";
          modal_elem.querySelector("#modal_submit").innerHTML = "Изменить";

          modal_elem.querySelector('#in_ads_id').value = json.adv_id;
          modal_elem.querySelector('#in_show').checked = json.show;
          for(let elem2 of document.querySelectorAll("#in_srv_id option")) {
            if(json.servers.includes(elem2.value)) {
              elem2.selected = true;
            }
          }
          modal_elem.querySelector('#in_msg_type').value = json.msg_type;
          modal_elem.querySelector('#in_msg_text').value = json.msg_text;
          modal_elem.querySelector('#in_date_from').value = json.date_from;
          modal_elem.querySelector('#in_date_to').value = json.date_to;
          modal_elem.querySelector('#in_hours').value = json.hours;
          modal_elem.querySelector('#in_day_of_week').value = json.day_of_week;
          modal_elem.querySelector('#in_is_vip').checked = json.is_vip;
          modal_elem.querySelector('#in_admin_flags').value = json.admin_flags;
          modal_elem.querySelector('#in_views').value = json.views;
          modal_elem.querySelector('#in_order').value = json.order;

          if(json.hud) {
            modal_elem.querySelector('#in_hud_color1').value = json.hud['color1'];
            modal_elem.querySelector('#in_hud_color2').value = json.hud['color2'];
            modal_elem.querySelector('#in_hud_effect').value = json.hud['effect'];
            modal_elem.querySelector('#in_hud_fadein').value = json.hud['fadein'];
            modal_elem.querySelector('#in_hud_fadeout').value = json.hud['fadeout'];
            modal_elem.querySelector('#in_hud_holdtime').value = json.hud['holdtime'];
            modal_elem.querySelector('#in_hud_fxtime').value = json.hud['fxtime'];
            modal_elem.querySelector('#in_hud_x').value = json.hud['x'];
            modal_elem.querySelector('#in_hud_y').value = json.hud['y'];
          }

          if(modal_elem.querySelector('#in_msg_type').value == 2) {
            document.querySelector("#group_hud").style.display = 'flex';
          } else {
            document.querySelector("#group_hud").style.display = 'none';
          }

          modal_elem.classList.add('active');
        } else {
          console.log("HTTP-Error: " + response.status);
        }
      })();
      return false;
    }
  }
}

document.querySelector("#in_msg_type").onchange = function(e) {
  if(e.target.value == 2) {
    document.querySelector("#group_hud").style.display = 'flex';
  } else {
    document.querySelector("#group_hud").style.display = 'none';
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
  elem2.innerHTML = elem2.innerHTML.replace(/\\n/g, '<br>');
  elem2.innerHTML += '</span>';
}
window.onload = function(e) {
  for(let elem of document.querySelectorAll('.colorize')) {
    colorize(elem);
  }
};
