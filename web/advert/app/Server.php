<?php
require __DIR__ . '/Libs/SourceQuery/bootstrap.php';
use xPaw\SourceQuery\SourceQuery;

require_once __DIR__.'/Libs/Validator.php';
use Valitron\Validator as V;
V::langDir(__DIR__.'/Libs/Validator/lang'); // always set langDir before lang.
V::lang('ru');

class Server {
	function beforeRoute($f3, $params) {
		(new Main())->beforeRoute($f3, $params);
	}
	function getList($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		$servers = $f3->get('db')->getTable('servers')->find(['user_id=?', $f3->get('user')->userId()]);
		if(count($servers) < 1) { // User have not servers yet
			die('0');
		}
		$servers = Main::castRes($servers, [
			'srv_id', 'ip', 'port', 'title', 'rcon', 'adv_time'
		]);
		foreach ($servers as $key => $server) {
			if(!empty($server['rcon'])) {
				$servers[$key]['rcon'] = $f3->get('no_rcon');
			}
		}
		die(json_encode(
			$servers
		));
	}
	public static function _getList() {
		global $f3;
		if(!$f3->get('user')->isLogged()) {
			return null;
		}
		$servers = $f3->get('db')->getTable('servers')->find(['user_id=?', $f3->get('user')->userId()]);
		if(count($servers) < 1) { // User have not servers yet
			return null;
		}
		$servers = Main::castRes($servers, [
			'srv_id', 'title',
		]);
		return $servers;
	}
	function saveField($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		$req = json_decode($f3->get('BODY'), true);
		if($req == null) die('0');
		$req = array_map('trim', $req);
		if(!isset($req['srv_id']) || !isset($req['field']) || !isset($req['value'])) {
			die('0');
		}
		if(!in_array($req['field'], [	'title', 'ip', 'port', 'adv_time'])) {
			die('0');
		}
		switch($req['field']) {
			case 'title':
				if(empty($req['value'])) die('0');
				break;
			case 'ip':
				if(empty($req['value'])) die('0');
				break;
			case 'port':
				if(empty($req['value'])) die('0');
				if(!is_numeric($req['value'])) die(0);
				break;
			case 'adv_time':
				if(!is_numeric($req['value'])) die(0);
				break;
		}
		$server = $f3->get('db')->getTable('servers')->load(['user_id=? and srv_id=?', [$f3->get('user')->userId(), $req['srv_id']] ]);
		if(!$server) {
			die('0');
		}
		$server[$req['field']] = $req['value'];
		try {
			$server->save();
		} catch (Exception $e) {
			die('0');
		}
		die('1');
	}
	function save($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		$req = json_decode($f3->get('BODY'), true);
		if($req == null) die('0');
		$req = $req['data'];
		if(!isset($req['srv_id']) || !is_numeric($req['srv_id'])) {
			die('0');
		}
		if(!isset($req['ip']) || !isset($req['port']) || !isset($req['title']) || !isset($req['adv_time']) || !isset($req['rcon'])) {
			die('0');
		}
		$req = array_map('trim', $req);
		if(empty($req['ip']) || empty($req['port']) || empty($req['title'])) {
			die('0');
		}
		if(!is_numeric($req['adv_time']) || !is_numeric($req['port'])) {
			die('0');
		}
		if($req['srv_id'] < 0) { // add new
			$server = $f3->get('db')->getTable('servers');
			$server->reset();
			$server->user_id = $f3->get('user')->userId();
			$server->ip = $req['ip'];
			$server->port = $req['port'];
			$server->title = $req['title'];
			$server->created = date("Y-m-d H:i:s", time());
			if(!empty($req['rcon'])) {
				$server->rcon = User::encryptUserData($f3->get('user')->data['iv'], $req['rcon']);
			} else {
				$server->rcon = null;
			}
			$server->adv_time = $req['adv_time'];
			try {
				$server->save();
			} catch (Exception $e) {
				die('0');
			}
		} else { // existing one
			$server = $f3->get('db')->getTable('servers')->load(['user_id=? and srv_id=?', [$f3->get('user')->userId(), $req['srv_id']] ]);
			if(!$server) {
				die('0');
			}
			$server->ip = $req['ip'];
			$server->port = $req['port'];
			$server->title = $req['title'];
			if($req['rcon'] == $f3->get('no_rcon')) {
				// Rcon not changed
			} else if(!empty($req['rcon'])) { // Rcon not empty and not default
				$server->rcon = User::encryptUserData($f3->get('user')->data['iv'], $req['rcon']);
			} else {
				$server->rcon = null;
			}
			$server->adv_time = $req['adv_time'];
			try {
				$server->save();
			} catch (Exception $e) {
				die('0');
			}
		}
		die('1');
	}
	function delete($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		if(!is_numeric($params['srv_id'])) {
			die(0);
		}
		$server = $f3->get('db')->getTable('servers')->load(['user_id=? and srv_id=?', [$f3->get('user')->userId(), $params['srv_id']] ]);
		if(!$server) {
			die('0');
		}
		$server->erase();
		die('1');
	}
	function update($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		if(!is_numeric($params['srv_id'])) {
			die(0);
		}
		$server = $f3->get('db')->getTable('servers')->load(['user_id=? and srv_id=?', [$f3->get('user')->userId(), $params['srv_id']] ]);
		if(!$server) {
			die('0');
		}
		if(strlen($server->rcon) < 1) die('0');
		$suc = '0';
		$query = new SourceQuery();
	  	try {
	  		$query->Connect($server['ip'], $server['port'], 1, SourceQuery::SOURCE );
	      $query->SetRconPassword(User::decryptUserData($f3->get('user')->data['iv'], $server['rcon']));
	      $suc = $query->Rcon("sm_adv_update;");
	  	} catch( Exception $e ) {
	  		// error_log($e->getMessage());
	      // echo "Internal error, check console.";
	  	} finally {
	  		$query->Disconnect();
	  	}
		die($suc);
	}
	function hotMsg($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		if(!is_numeric($params['srv_id'])) {
			die(0);
		}
		$server = $f3->get('db')->getTable('servers')->load(['user_id=? and srv_id=?', [$f3->get('user')->userId(), $params['srv_id']] ]);
		if(!$server) {
			die('0');
		}
		if(strlen($server->rcon) < 1) die('0');
		$req = json_decode($f3->get('BODY'), true);
		if($req == null) die('0');
		$req = $req['data'];
		$v = new V($req);
		$v->rules([
			'required' => [
				['msg_text'],
				['msg_type'],
				['hud', true],
			],
			'optional' => [
				['count'], ['cooldown']
			],
			'numeric' => [
				['count'],
				['cooldown'],
				['msg_type'],
			],
			'min' => [
				['count', 0],
				['cooldown', 5],
				['msg_type', 0],
			],
			'max' => [
				['count', 5],
				['cooldown', 60],
				['msg_type', 3],
			]
		]);
		$v->labels([
			'count' => 'Кол-во повторов',
			'cooldown' => 'Периодичность',
			'msg_text' => 'Текст сообщения',
			'msg_type' => 'Тип сообщения'
		]);
		if(!$v->validate()) {
			die(json_encode($v->errors()));
		}
		$msg = $f3->get('db')->getTable('hot_msgs');
		$msg->reset();
		$msg->user_id = $f3->get('user')->userId();
		$msg->srv_id = $server->srv_id;
		$msg->msg_text = $req['msg_text'];
		$msg->msg_type = $req['msg_type'];
		$msg->created = date("Y-m-d H:i:s", time());
		if(isset($req['count']) && !empty($req['count'])) {
			$msg->count = $req['count'];
		}
		if(isset($req['cooldown'])) {
			$msg->cooldown = $req['cooldown'];
		}
		if(isset($req['cmd'])) {
			$msg->cmd = $req['cmd'];
		}
		if(!empty($req['hud']) && $msg->msg_type == 1) {
			ini_set('serialize_precision', -1);
			$msg->hud = json_encode($req['hud']);
		}
		$msg->save();

		$suc = 'Сообщение не отправлено';
		$query = new SourceQuery();
	  	try {
	  		$query->Connect($server['ip'], $server['port'], 1, SourceQuery::SOURCE );
	      $query->SetRconPassword(User::decryptUserData($f3->get('user')->data['iv'], $server['rcon']));
	      $suc = $query->Rcon("sm_adv_hot ".$msg->msg_id.";");
	  	} catch( Exception $e ) {
	  		// error_log($e->getMessage());
	      // echo "Internal error, check console.";
	  	} finally {
	  		$query->Disconnect();
	  	}
		die($suc);
	}
	function encryptRcons($iv) {
		global $f3;
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		$servers = $f3->get('db')->getTable('servers')->find(['user_id=?', $f3->get('user')->userId()]);
		if(count($servers) < 1) {
			return;
		}
		foreach ($servers as $server) {
			if(empty($server->rcon)) continue;
			if(User::decryptUserData($iv, $server->rcon) === FALSE) {
				$server->rcon = User::encryptUserData($iv, $server->rcon);
				$server->save();
			}
		}
	}
}
