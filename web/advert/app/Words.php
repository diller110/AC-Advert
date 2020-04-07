<?php
class Words {
	function beforeRoute($f3, $params) {
		(new Main())->beforeRoute($f3, $params);
	}
	function getList($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		$words = $f3->get('db')->getTable('magic_words')->find(['user_id=?', $f3->get('user')->userId()]);
		if(count($words) < 1) { // User have not servers yet
			die('0');
		}
		$words = Main::castRes($words, [
			'word_id', 'key', 'value'
		]);
		die(json_encode(
			$words
		));
	}
	function saveField($f3, $params) {
		if(!$f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		$req = json_decode($f3->get('BODY'), true);
		if($req == null) die('0');
		if(!isset($req['word_id']) || !isset($req['field']) || !isset($req['value'])) {
			die('0');
		}
		if(!in_array($req['field'], [	'key', 'value'])) {
			die('0');
		}

		$word = $f3->get('db')->getTable('magic_words')->load(['user_id=? and word_id=?', [$f3->get('user')->userId(), $req['word_id']] ]);
		if(!$word) {
			die('0');
		}
		$word[$req['field']] = $req['value'];
		try {
			$word->save();
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
		if(!isset($req['word_id']) || !is_numeric($req['word_id'])) {
			die('0');
		}
		if(!isset($req['key']) || !isset($req['value'])) {
			die('0');
		}
		if(empty($req['key']) || empty($req['value'])) {
			die('0');
		}
		if($req['word_id'] < 0) { // add new
			$word = $f3->get('db')->getTable('magic_words');
			$word->reset();
			$word->user_id = $f3->get('user')->userId();
			$word->key = $req['key'];
			$word->value = $req['value'];
			try {
				$word->save();
			} catch (Exception $e) {
				die('0');
			}
		} else { // existing one
			$word = $f3->get('db')->getTable('magic_words')->load(['user_id=? and word_id=?', [$f3->get('user')->userId(), $req['word_id']] ]);
			if(!$word) {
				die('0');
			}
			$word->key = $req['key'];
			$word->value = $req['value'];
			try {
				$word->save();
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
		if(!is_numeric($params['word_id'])) {
			die(0);
		}
		$word = $f3->get('db')->getTable('magic_words')->load(['user_id=? and word_id=?', [$f3->get('user')->userId(), $params['word_id']] ]);
		if(!$word) {
			die('0');
		}
		$word->erase();
		die('1');
	}
}
