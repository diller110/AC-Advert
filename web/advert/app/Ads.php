<?php
require_once __DIR__.'/Libs/Validator.php';
use Valitron\Validator as V;
V::langDir(__DIR__.'/Libs/Validator/lang'); // always set langDir before lang.
V::lang('ru');

class Ads {
	function beforeRoute($f3, $params) {
		(new Main())->beforeRoute($f3, $params);
	}
	function getList($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		$res = [
			'servers' => null,
			'ads' => null,
		];

		$servers = Server::_getList();
		if($servers == null) {
			die(json_encode($res));
		}
		$tmp = [];
		foreach ($servers as $key => $value) {
			$tmp[$value['srv_id']] = $value;
		}
		$res['servers'] = $tmp;

		$ads = $f3->get('db')->getTable('advert')->find(['user_id=?', $f3->get('user')->userId()]);
		if(count($ads) < 1) { // User have not servers yet
			die(json_encode($res));
		}
		$ads = Main::castRes($ads, [
			'adv_id', 'msg_type', 'msg_text',
			'date_from', 'date_to', 'hours',
			'is_vip', 'admin_flags', 'views',
			'day_of_week', 'show', 'order',
			'cmd'
		]);
		$default_hud = [
			'color1' => '255 255 255 255',
			'color2' => '255 255 255 255',
			'effect' => 0,
			'fadein' => 0.1,
			'fadeout' => 0.1,
			'holdtime' => 0.1,
			'fxtime' => 0.1,
			'x' => 0.1,
			'y' => 0.1,
		];
		foreach ($ads as $key => $adv) {
			if($adv['msg_type'] != 1) {
				$ads[$key]['hud'] = $default_hud;
				continue;
			}
			$hud = $f3->get('db')->getTable('hud_style')->load(['adv_id=?', $adv['adv_id']]);
			if($hud) {
				$hud = $hud->cast();
				unset($hud['adv_id']);
			} else {
				$hud = $default_hud;
			}
			$ads[$key]['hud'] = $hud;
		}
		$db = $f3->get('db')->db;
		foreach ($ads as $key => $adv) {
			$tmp = $db->exec(
				'select srv_id from '.$f3->get('db_prefix').'server_ads where adv_id=?',
				$adv['adv_id']
			);
			$ads[$key]['servers'] = [];
			foreach ($tmp as $val) {
				$ads[$key]['servers'][] = $val['srv_id'];
			}
		}
		$res['ads'] = $ads;
		die(json_encode($res));
	}
	function save($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		$req = json_decode($f3->get('BODY'), true);
		if($req == null) die('0');
		$req = $req['data'];

		if(isset($req['show'])) { // Html Switch fix...
			if($req['show'] == true) {
				$req['show'] = 1;
			} else {
				$req['show'] = 0;
			}
		}

		$v = new V($req);
		$v->rule('required', ['adv_id', 'msg_type', 'msg_text', 'servers']);
		$v->rule('numeric', ['adv_id', 'msg_type', 'is_vip', 'views', 'show', 'order']);
		$v->rule('lengthMin', 'msg_text', 3);
		$v->rule('array', 'servers');
		if($req['msg_type'] == 1) {
			$v->rule('required', ['hud', 'hud.color1', 'hud.color2', 'hud.effect', 'hud.fadein', 'hud.fadeout', 'hud.fxtime', 'hud.holdtime', 'hud.x', 'hud.y']);
			$v->rule('numeric', ['hud.effect', 'hud.fadein', 'hud.fadeout', 'hud.fxtime', 'hud.holdtime', 'hud.x', 'hud.y']);
			$v->rule('lengthMin', ['hud.color1', 'hud.color2'], 3);
		}
		if(!$v->validate()) {
			die('0');
		}

		$servers = Server::_getList();
		if($servers == null) {
			die('0');
		}
		$servers = array_column($servers, 'srv_id');
		$diffs = array_diff($req['servers'], $servers);
		if(count($diffs)) {
			die('0');
		}

		$adv = null;
		if($req['adv_id'] < 0) { // add new
			$adv = $f3->get('db')->getTable('advert');
			$adv->reset();
			$adv->user_id = $f3->get('user')->userId();
		} else { // existing one
			$adv = $f3->get('db')->getTable('advert')->load(['user_id=? and adv_id=?', [$f3->get('user')->userId(), $req['adv_id']] ]);
			if(!$adv) {
				die('0');
			}
		}
		$adv->msg_type = $req['msg_type'];
		$adv->msg_text = $req['msg_text'];
		$adv->date_from = $req['date_from'];
		$adv->date_to = $req['date_to'];
		$adv->hours = $req['hours'];
		$adv->is_vip = $req['is_vip'];
		$adv->admin_flags = $req['admin_flags'];
		$adv->views = $req['views'];
		$adv->day_of_week = $req['day_of_week'];
		$adv->show = $req['show'];
		$adv->order = $req['order'];
		$adv->cmd = $req['cmd'];

		try {
			$adv->save();
		} catch (Exception $e) {
			die('0');
		}

		if($adv->msg_type == 1) {
			$hud = $f3->get('db')->getTable('hud_style')->load(['adv_id=?', $adv->adv_id]);
			if(!$hud) {
				$hud = $f3->get('db')->getTable('hud_style');
				$hud->reset();
				$hud->adv_id = $adv->adv_id;
			}
			$hud->color1 = $req['hud']['color1'];
			$hud->color2 = $req['hud']['color2'];
			$hud->effect = $req['hud']['effect'];
			$hud->fadein = $req['hud']['fadein'];
			$hud->fadeout = $req['hud']['fadeout'];
			$hud->holdtime = $req['hud']['holdtime'];
			$hud->x = $req['hud']['x'];
			$hud->y = $req['hud']['y'];
			$hud->fxtime = $req['hud']['fxtime'];
			try {
				$hud->save();
			} catch (Exception $e) {
				die('0');
			}
		}

		if($req['adv_id'] > 0) {
			$f3->get('db')->db->exec('delete from '.$f3->get('db_prefix').'server_ads where adv_id=?', $adv->adv_id);
		}
		$server_ads = $f3->get('db')->getTable('server_ads');
		foreach ($req['servers'] as $server) {
			$server_ads->reset();
			$server_ads->srv_id = $server;
			$server_ads->adv_id = $adv->adv_id;
			$server_ads->save();
		}
		die('1');
	}
	function delete($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		if(!is_numeric($params['adv_id'])) {
			die(0);
		}
		$adv = $f3->get('db')->getTable('advert')->load(['user_id=? and adv_id=?', [$f3->get('user')->userId(), $params['adv_id']] ]);
		if(!$adv) {
			die('0');
		}
		if($adv->msg_type == 1) { // HUD
			$hud = $f3->get('db')->getTable('hud_style')->load(['adv_id=?', $adv->adv_id]);
			if($hud) {
				$hud->erase();
			}
		}
		$adv->erase();
		die('1');
	}
}
