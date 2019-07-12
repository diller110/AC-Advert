<?php
require __DIR__ . '/Libs/SourceQuery/bootstrap.php';
use xPaw\SourceQuery\SourceQuery;
class Ex {
  function beforeRoute($f3) {
    if($f3->get('PARAMS')[0] != '/login') {
      if(!$f3->get('SESSION.logged')) {
        $f3->reroute('@login');
      }
    }
  }
	function index($f3, $params) {
		Main::layout();
		$f3->set('cur_page', 'main');

		$f3->set('content', 'cell/servers.htm');

		$servers = $f3->get('db')->server()->list2();
		if($servers['status']) {
			$f3->set('servers', $servers['res']);
		}

		Main::render();
	}
  function rcon($f3, $params) {
    if(!is_numeric($params['id'])) {
      die();
    }
    $server = $f3->get('db')->server()->get($params['id']);
    if(!$server['status']) {
      die("Not found");
    }
    $server = $server['res'];
    $query = new SourceQuery();
    $suc = 0;
  	try {
  		$query->Connect($server['ip'], $server['port'], 1, SourceQuery::SOURCE );
      $query->SetRconPassword($server['rcon']);
  		$query->Rcon("sm_ac_adv_update;");
      $suc = 1;
  	} catch( Exception $e ) {
  		error_log($e->getMessage());
      echo "Internal error, check console.";
  	} finally {
  		$query->Disconnect();
  	}
    $f3->reroute("@main?reloaded=".$suc);
  }
  function server($f3, $params) {
    if(!is_numeric($params['id'])) {
      die();
    }
    $server = $f3->get('db')->server()->get($params['id']);
    if($server['status']) {
      echo json_encode($server['res']->cast());
    } else {
      echo "Not found";
    }
  }
  function serverPost($f3, $params) {
    if(!is_numeric($f3->get('POST')['srv_id'])) {
      die();
    }
    if($f3->get('POST')['srv_id'] == -1) {
      $f3->get('db')->server()->add($f3->get('POST'));
      $f3->reroute("@main");
    } else {
      $f3->get('db')->server()->edit($f3->get('POST'));
      $f3->reroute("@main");
    }
  }
  function serverDelete($f3, $params) {
    if(!is_numeric($params['id'])) {
      die();
    }
    $f3->get('db')->server()->delete($params['id']);
    $f3->reroute("@main");
  }
  function words($f3, $params) {
    if(!is_numeric($params['id'])) {
      die();
    }
    $words = $f3->get('db')->words()->get($params['id']);
    if($words['status']) {
      echo json_encode($words['res']->cast());
    } else {
      echo "Not found";
    }
  }
  function wordsPost($f3, $params) {
    if(!is_numeric($f3->get('POST')['word_id'])) {
      die();
    }
    if($f3->get('POST')['word_id'] == -1) {
      $f3->get('db')->words()->add($f3->get('POST'));
      $f3->reroute("@words");
    } else {
      $f3->get('db')->words()->edit($f3->get('POST'));
      $f3->reroute("@words");
    }
  }
  function wordsDelete($f3, $params) {
    if(!is_numeric($params['id'])) {
      die();
    }
    $f3->get('db')->words()->delete($params['id']);
    $f3->reroute("@words");
  }

  function ads($f3, $params) {
    if(!is_numeric($params['id'])) {
      die();
    }
    $ads = $f3->get('db')->ads()->get($params['id']);
    if($ads['status']) {
      $ads = $ads['res']->cast();
      $ads['date_from'] = date("Y-m-d", strtotime($ads['date_from']));
      $ads['date_to'] = date("Y-m-d", strtotime($ads['date_to']));
      $servers = $f3->get('db')->adsServers($ads['adv_id']);
      if($servers['status']) {
        foreach ($servers['res'] as $server) {
          $ads['servers'][] = $server['srv_id'];
        }
      }
      if($ads['msg_type'] == 2) {
        $hud = $f3->get('db')->adsHud($ads['adv_id']);
        if($hud['status']) {
          $ads['hud'] = $hud['res']->cast();
        }
      }
      echo json_encode($ads);
    } else {
      echo "Not found";
    }
  }
  function adsPost($f3, $params) {
    if(!is_numeric($f3->get('POST')['adv_id'])) {
      die();
    }

    $f3->set('POST', array_filter($f3->get('POST')));

    if($f3->exists('POST.show') && $f3->get('POST.show') == 'on') {
      $f3->set('POST.show', 1);
    } else {
      $f3->set('POST.show', 0);
    }
    if($f3->exists('POST.is_vip') && $f3->get('POST.is_vip') == 'on') {
      $f3->set('POST.is_vip', 1);
    } else {
      $f3->set('POST.is_vip', 0);
    }

    if(!$f3->exists('POST.date_from') || $f3->get('POST.date_from') == '1970-01-01') {
      $f3->set('POST.date_from', 0);
    }
    if(!$f3->exists('POST.date_to') || $f3->get('POST.date_to') == '1970-01-01') {
      $f3->set('POST.date_to', 0);
    }

    if($f3->get('POST.adv_id') == -1) {
      $res = $f3->get('db')->ads()->add($f3->get('POST'));
      if($res['status']) {
        $id = $res['res']['adv_id'];
        foreach ($f3->get('POST.srv_id') as $key => $value) {
          $record = $f3->get('db')->getTable('server_ads');
          $record->srv_id = $value;
          $record->adv_id = $id;
          $record->save();
        }
        if($f3->get('POST.msg_type') == 2) {
          $hud = $f3->get('db')->getTable('hud_style');
          $hud->adv_id = $id;
          $hud->copyFrom('POST.hud');
          $hud->save();
        }
      }
      $f3->reroute("@ads");
    } else {
      $res = $f3->get('db')->ads()->edit($f3->get('POST'));
      if($res['status']) {
        $id = $res['res']['adv_id'];
        $res2 = $f3->get('db')->getTable('server_ads')->find(['adv_id=?', $id]);
        foreach ($res2 as $record) {
          $record->erase();
        }
        foreach ($f3->get('POST.srv_id') as $key => $value) {
          $record = $f3->get('db')->getTable('server_ads');
          $record->srv_id = $value;
          $record->adv_id = $id;
          $record->save();
        }
        if($f3->get('POST.msg_type') == 2) {
          $hud = $f3->get('db')->getTable('hud_style')->load(['adv_id=?', $id]);
          if(empty($hud)) {
            $hud = $f3->get('db')->getTable('hud_style');
            $hud->adv_id = $id;
          }
          $hud->copyFrom('POST.hud');
          $hud = $hud->save();
        }
      }
      $f3->reroute("@ads");
    }
  }
  function adsDelete($f3, $params) {
    if(!is_numeric($params['id'])) {
      die();
    }
    $f3->get('db')->ads()->delete($params['id']);
    $f3->reroute("@ads");
  }

}
