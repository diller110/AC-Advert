<?php

class Api {
	private $user = null;
	private $server = null;
	private $custom = [
		'alt_colors' => false
	];

	function beforeRoute($f3, $params) {
		if(!$f3->exists('HEADERS.Authorization')) {
			if(isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
				$f3->set('HEADERS.Authorization', ''.$_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
			} else if(isset($_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION']) && !empty($_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION'])) {
				$f3->set('HEADERS.Authorization', ''.$_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION']);
			} else if($f3->exists('GET.authorization') && !empty($f3->get('GET.authorization'))) {
				$f3->set('HEADERS.Authorization', ''.$f3->get('GET.authorization'));
			}
		}

		if(!$f3->exists('HEADERS.Authorization') || !$f3->exists('HEADERS.Serverport')) {
			die(json_encode([
				'error' => 'No authorization info.'
			]));
		}
		if(empty($f3->get('HEADERS.Authorization')) || strlen($f3->get('HEADERS.Authorization')) < 20) {
			die(json_encode([
				'error' => 'Invalid account token('.$f3->get('HEADERS.Authorization').').'
			]));
		}
		if(!is_numeric($f3->get('HEADERS.Serverport'))) {
			die(json_encode([
				'error' => 'Invalid server port.'
			]));
		}
		$user = $f3->get('db')->getTable('users')->load(['token=?', $f3->get('HEADERS.Authorization')]);
		if(!$user) {
			die(json_encode([
				'error' => 'User not found. Token: '.$f3->get('HEADERS.Authorization')
			]));
		}

		$ip = $f3->get('IP');
		if($f3->exists('HEADERS.Forceip')) {
			$ip = $f3->get('HEADERS.Forceip');
		}

		$server = $f3->get('db')->getTable('servers')->load(['user_id=? and ip=? and port=?', [$user->user_id, $ip, $f3->get('HEADERS.Serverport')] ]);
		if(!$server) {
			die(json_encode([
				'error' => 'Server not in user\'s servers list.'.$ip.':'.$f3->get('HEADERS.Serverport')
			]));
		}
		$this->user = $user;
		$this->server = $server;

		if($f3->exists('HEADERS.Altcolors')) {
			if($f3->get('HEADERS.Altcolors')) {
				$this->custom['alt_colors'] = true;
			}
		}
	}
	function auth($f3, $params) {
		die(json_encode([
			'time' => intval($this->server->adv_time),
			'title' => $this->server->title,
		]));
	}
	function get($f3, $params) {
		$ads = $f3->get('db')->db->exec(
			'select adv_id, msg_type, msg_text, UNIX_TIMESTAMP(date_from) as date_from, UNIX_TIMESTAMP(date_to) as date_to, is_vip, views, hours, admin_flags, day_of_week, cmd
			from '.$f3->get('db_prefix').'advert
			where
				adv_id in (select adv_id from '.$f3->get('db_prefix').'server_ads where srv_id = ?) and
				`show` = 1 and ((date_from is null or now()>=date_from) OR (date_to is null or now()<=date_to))
			order by `order`, adv_id',
			$this->server->srv_id
		);
		if(count($ads) < 1) {
			die(json_encode([
				'error' => 'Server have no ads.'
			]));
		}
		$words = $f3->get('db')->getTable('magic_words')->find(['user_id=?', $this->user->user_id]);
		if($words) {
			$words = Main::castRes($words, ['key', 'value']);
			$words = array_column($words, 'value', 'key');
			foreach ($words as $key => $value) { // prevents key-word-key-word-key... recursion
				$words['{'.$key.'}'] = str_replace('{'.$key.'}', '{-'.$key.'}', $value);
				unset($words[$key]);
			}
			foreach ($ads as $key => $value) {
				$ads[$key]['msg_text'] = self::formatWords($ads[$key]['msg_text'], $words);
			}
		}
		foreach ($ads as $key => $value) {
			$ads[$key]['msg_text'] = strtr(
				$value['msg_text'], [
					'{/hostname_db}' => $this->server->title,
					'{/addr}' => $this->server->ip,
					'{/port}' => $this->server->port,
					'{\\nick}' => '{\\.nick}',
				]
			);

			if(isset($this->custom['alt_colors']) && $this->custom['alt_colors']) {
				$ads[$key]['msg_text'] = self::formatColors_Alt($ads[$key]['msg_text']);
			} else {
				$ads[$key]['msg_text'] = self::formatColors($ads[$key]['msg_text']);
			}
			$ads[$key]['msg_type'] = intval($ads[$key]['msg_type']);
			$ads[$key]['is_vip'] = intval($ads[$key]['is_vip']);
			$ads[$key]['changeable'] = (strpos($value['msg_text'], '{/') !== FALSE);
			$ads[$key]['userable'] = (strpos($value['msg_text'], '{\\') !== FALSE);
			if($value['msg_type'] == 0) {
				if($this->custom['alt_colors']) {
					$ads[$key]['msg_text'] = "\x01".$ads[$key]['msg_text'];
					$ads[$key]['msg_text'] = str_replace("\\n", "", $ads[$key]['msg_text']);
				} else {
					$ads[$key]['msg_text'] = " ".$ads[$key]['msg_text'];
					$ads[$key]['msg_text'] = str_replace("\\n", "\\n ", $ads[$key]['msg_text']);
				}
			} else if($value['msg_type'] == 1) {
				$hud = $f3->get('db')->getTable('hud_style')->load(['adv_id=?', $value['adv_id']]);
				if($hud) {
					$hud = $hud->cast();
					unset($hud['adv_id']);
					$hud['fadein'] = floatval($hud['fadein']);
					$hud['fadeout'] = floatval($hud['fadeout']);
					$hud['holdtime'] = floatval($hud['holdtime']);
					$hud['fxtime'] = floatval($hud['fxtime']);
					$hud['x'] = floatval($hud['x']);
					$hud['y'] = floatval($hud['y']);
					$ads[$key]['hud'] = $hud;
				} else {
					$ads[$key]['hud'] = [
						'color1' => '255 255 255 255',	'color2' => '255 255 255 255',
						'fadein' => 0.1,	'fadeout' => 0.1,
						'holdtime' => 0.1,	'fxtime' => 0.1,
						'x' => 0.1,	'y' => 0.1, 'effect' => 0,
					];
				}
			}
		}
		die(json_encode(['ads' => $ads], JSON_PRESERVE_ZERO_FRACTION+JSON_UNESCAPED_UNICODE  ));
	}
	function test($f3, $params) {

	}
	/*
	 *	/api/hotmsg/@msg_id
	 */
	function hotmsg($f3, $params) {
		if(!is_numeric($params['msg_id'])) {
			die(json_encode(['error' => 'Msg ID must be numeric']));
		}
		$msg = $f3->get('db')->getTable('hot_msgs')->load([
			'user_id=? and srv_id=? and msg_id=?',
			[$this->user->user_id, $this->server->srv_id, $params['msg_id']]
		]);
		if(!$msg) {
			die(json_encode(['error' => 'Msg ID not valid for this server']));
		}
		$msg = Main::cast($msg, [
			'msg_id', 'msg_type', 'msg_text', 'count', 'cooldown', 'cmd', 'hud'
		]);
		if($msg['msg_type'] == 1) { // if hud
			$msg['hud'] = json_decode($msg['hud']);
			if($msg['hud'] === null) {
				die(json_encode(['error' => 'Hot msg have incorrect "hud" data.']));
			}
		}

		$words = $f3->get('db')->getTable('magic_words')->find(['user_id=?', $this->user->user_id]);
		if($words) {
			$words = Main::castRes($words, ['key', 'value']);
			$words = array_column($words, 'value', 'key');
			foreach ($words as $key => $value) { // prevents key-word-key-word-key... recursion
				$words['{'.$key.'}'] = str_replace('{'.$key.'}', '{-'.$key.'}', $value);
				unset($words[$key]);
			}
			$msg['msg_text'] = self::formatWords($msg['msg_text'], $words);
		}
		$msg['msg_text'] = strtr(
			$msg['msg_text'], [
				'{/hostname_db}' => $this->server->title,
				'{/addr}' => $this->server->ip,
				'{/port}' => $this->server->port,
				'{\\nick}' => '{\\.nick}',
			]
		);
		if(isset($this->custom['alt_colors']) && $this->custom['alt_colors']) {
			$msg['msg_text'] = self::formatColors_Alt($msg['msg_text']);
		} else {
			$msg['msg_text'] = self::formatColors($msg['msg_text']);
		}

		if($msg['msg_type'] == 0) {
			if($this->custom['alt_colors']) {
				$msg['msg_text'] = "\x01".$msg['msg_text'];
				$msg['msg_text'] = str_replace("\\n", "", $msg['msg_text']);
			} else {
				$msg['msg_text'] = " ".$msg['msg_text'];
				$msg['msg_text'] = str_replace("\\n", "\\n ", $msg['msg_text']);
			}
		}
		$msg['changeable'] = (strpos($msg['msg_text'], '{/') !== FALSE);
		$msg['userable'] = (strpos($msg['msg_text'], '{\\') !== FALSE);
		$msg['is_vip'] = intval($msg['is_vip']);

		die(json_encode(['msg' => $msg], JSON_UNESCAPED_UNICODE));
	}
	static function formatWords($text, $words) {
		$c = 0;
		while(preg_match('/{([A-Za-z0-9_]+)}/', $text) === 1 && $c++<3) {
			$text = strtr($text, $words);
		}
		return $text;
	}
	static function formatColors($text) {
		return strtr(
			$text, [
				"{\\01}" => "\x01",	"{\\02}" => "\x02",	"{\\03}" => "\x03",	"{\\04}" => "\x04",
				"{\\05}" => "\x05",	"{\\06}" => "\x06",	"{\\07}" => "\x07",	"{\\08}" => "\x08",
				"{\\09}" => "\x09",	"{\\10}" => "\x0A",	"{\\11}" => "\x0B",	"{\\12}" => "\x0C",
				"{\\13}" => "\x0D",	"{\\14}" => "\x0E",	"{\\15}" => "\x0F",	"{\\16}" => "\x10",
			]
		);
	}
	static function formatColors_Alt($text) {
		return strtr(
			$text, [
				"{\\01}" => "\x01",		"{\\02}" => "\x07FF0000",	"{\\03}" => "\x07BA81F0",	"{\\04}" => "\x0740FF40",
				"{\\05}" => "\x07BFFF90",	"{\\06}" => "\x07A2FF47",	"{\\07}" => "\x07FF4040",	"{\\08}" => "\x07C5CAD0",
				"{\\09}" => "\x07EDE47A",	"{\\10}" => "\x07B0C3D9",	"{\\11}" => "\x075E98D9",	"{\\12}" => "\x074B69FF",
				"{\\13}" => "\x07B0C3D9",	"{\\14}" => "\x07D32CE6",	"{\\15}" => "\x07EB4B4B",	"{\\16}" => "\x07E4AE39",
			]
		);
	}
}
