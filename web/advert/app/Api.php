<?php

class Api {
	private $user = null;
	private $server = null;
	function beforeRoute($f3, $params) {
		if(!$f3->exists('HEADERS.Authorization') || !$f3->exists('HEADERS.Serverport')) {
			die(json_encode([]));
		}
		if(empty($f3->get('HEADERS.Authorization')) || strlen($f3->get('HEADERS.Authorization')) < 20) {
			die(json_encode([
				'error' => 'Invalid account token.'
			]));
		}
		if(!is_numeric($f3->get('HEADERS.Serverport'))) {
			die(json_encode([]));
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
	}
	function test($f3, $params) {
		die(json_encode([
			$this->server->adv_time
		]));
		die(json_encode([
			$f3->get('IP'),
			$f3->get('HEADERS.Authorization'),
			$f3->get('HEADERS')
		]));
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
		if(count($words)) {
			$words = Main::castRes($words, ['key', 'value']);
			foreach($words as $key => $v) {
			  unset($words[$key]);
			  $words[$v['key']] = str_replace('{'.$v['key'].'}', '{-'.$v['key'].'}', $v['value']);
			}
			foreach ($ads as $key => $value) {
				$ads[$key]['msg_text'] = $this->formatWords($ads[$key]['msg_text'], $words);
			}
		}
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
		foreach ($ads as $key => $value) {
			$ads[$key]['msg_text'] =
				str_replace('{/hostname_db}', $this->server->title,
				str_replace('{/addr}', $this->server->ip,
				str_replace('{/port}', $this->server->port,
				$value['msg_text'])));
			$ads[$key]['msg_text'] = str_replace("{\\nick}", "{\\.nick}", $ads[$key]['msg_text']);
			$ads[$key]['msg_text'] = $this->formatColors($ads[$key]['msg_text']);
			$ads[$key]['msg_type'] = intval($ads[$key]['msg_type']);
			$ads[$key]['is_vip'] = intval($ads[$key]['is_vip']);
			$ads[$key]['changeable'] = (strpos($value['msg_text'], '{/') !== FALSE);
			$ads[$key]['userable'] = (strpos($value['msg_text'], '{\\') !== FALSE);
			if($value['msg_type'] == 0) {
				$ads[$key]['msg_text'] = " ".$ads[$key]['msg_text'];
				$ads[$key]['msg_text'] = str_replace("\\n", "\\n ", $ads[$key]['msg_text']);
			}
			if($value['msg_type'] == 1) {
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
					$ads[$key]['hud'] = $default_hud;
				}
			}
		}
		die(json_encode(['ads' => $ads], JSON_PRESERVE_ZERO_FRACTION+JSON_UNESCAPED_UNICODE  ));
	}
	function formatWords($text, $words, $c = 0) {
		if($c > 3) return $text;
		$matches = null;
		if(preg_match_all('/{([A-Za-z0-9_]+)}/', $text, $matches) < 1) {
			return $text;
		}
		foreach($matches[1] as $key => $value) {
			if(!isset($words[$value])) {
				$text = str_replace('{'.$value.'}', '{-'.$value.'}', $text);
				continue;
			}
			$text = str_replace('{'.$value.'}', $words[$value], $text);
		}
		if(preg_match('/{([A-Za-z0-9_]+)}/', $text) == 1) {
			$text = formatWords($text, $words, $c+1);
		}
		return $text;
	}
	function formatColors($text) {
	  return str_replace("{\\01}", "\x01",
	          str_replace("{\\02}", "\x02",
	          str_replace("{\\03}", "\x03",
	          str_replace("{\\04}", "\x04",
	          str_replace("{\\05}", "\x05",
	          str_replace("{\\06}", "\x06",
	          str_replace("{\\07}", "\x07",
	          str_replace("{\\08}", "\x08",
	          str_replace("{\\09}", "\x09",
	          str_replace("{\\10}", "\x0A",
	          str_replace("{\\11}", "\x0B",
	          str_replace("{\\12}", "\x0C",
	          str_replace("{\\13}", "\x0D",
	          str_replace("{\\14}", "\x0E",
	          str_replace("{\\15}", "\x0F",
	          str_replace("{\\16}", "\x10",
	          $text))))))))))))))));
	}
}
