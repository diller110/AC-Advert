<?php
use Jumbojett\OpenIDConnectClient;
class Main {
	function beforeRoute($f3, $params) {
		if($f3->get('user')->isLogged()) {
			return;
		}
		$allowed_pages = [
			'/login',
			'/offer'
		];
		if(!in_array($f3->get('PARAMS')[0], $allowed_pages)) {
			$f3->reroute('@login');
		}
	}
	function offer($f3, $params) {
		Main::layout();
		$f3->set('content', 'cell/offer.htm');
		Main::render();
	}
	function logout($f3, $params) {
		if($f3->get('user')->isLogged()) {
			$f3->get('user')->logout();
		}
		$f3->reroute('@login');
	}
	function login($f3, $params) {
		if($f3->get('user')->isLogged()) {
			$f3->reroute('@main');
		}
		Main::layout();
		$f3->set('content', 'cell/login.htm');
		Main::render();
	}
	function index($f3, $params) {
		Main::layout();
		$f3->set('content', 'cell/servers.htm');
		$f3->set('V.curPage', 'servers');
		$f3->set('V.newButton', 'Добавить сервер');
		Main::render();
	}
	function words($f3, $params) {
		Main::layout();
		$f3->set('content', 'cell/words.htm');
		$f3->set('V.curPage', 'words');
		$f3->set('V.newButton', 'Добавить магическую фразу');
		Main::render();
	}
	function ads($f3, $params) {
		Main::layout();
		$f3->set('content', 'cell/ads.htm');
		$f3->set('V.curPage', 'ads');
		$f3->set('V.newButton', 'Добавить рекламу');
		Main::render();
	}

	static function layout() {
		global $f3;
		$f3->set('flash', function($key, $unset = false) {
			global $f3;
			if(!$f3->exists($f3->get('skey').'flash.'.$key)) {
				return null;
			}
			$val = $f3->get($f3->get('skey').'flash.'.$key);
			if($unset) {
				$f3->clear($f3->get('skey').'flash.'.$key);
			}
			return $val;
		});
		$f3->set('V.ifeq', function($key, $value, $true, $false = null) {
			global $f3;
			if(!$f3->exists($key)) {
				return $false;
			}
			if($f3->get($key) == $value) {
				return $true;
			}
			return $false;
		});
		$f3->set('V.if', function($key, $true, $false = null) {
			global $f3;
			if(!$f3->exists($key)) {
				return $false;
			}
			return $true;
		});
	}
	static function render() {
		global $f3;
		if($f3->get('DEBUG') == 3 && $f3->exists('GET.cs')) {
			try {
				require_once __DIR__.'/Libs/less.php/Autoloader.php';
				Less_Autoloader::register();
				$less = new Less_Parser(array( 'sourceMap' => true, 'compress'=>true ));
				$files = [
						'styles'
				];
				foreach ($files as $key => $value) {
					if(strpos($value, 'styles')===FALSE) continue;
					$less = new Less_Parser(array( 'sourceMap' => true, 'compress'=>true ));
					$less->parseFile(dirname(__DIR__).'/view/http/css/'.$value.'.less', $f3->get('BASE').'/css/');
					file_put_contents(dirname(__DIR__).'/view/http/css/'.$value.'.css',  $less->getCss());
				}
			} catch(Exception $e) {
			    echo $e->getMessage();
			}
		}
		echo \Template::instance()->render('layout.htm');
	}
	/*
	 * Cast DB record/records(as object) to php array
	 * array $fields - filter keys that not in array 
	*/
	static function cast($res, $fields = null) {
		if(is_array($res)) {
			foreach ($res as $key => $value) {
				$res[$key] = self::cast($value, $fields);
			}
		} else {
			$res = $res->cast();
			if($fields) {
				foreach ($res as $key => $value) {
					if(!in_array($key, $fields)) {
						unset($res[$key]);
					}
				}
			}
		}
		return $res;
	}
	static function castRes($res, $fields = null) {
		foreach ($res as $key => $value) {
			$res[$key] = $value->cast();
			if($fields) {
				foreach ($res[$key] as $key2 => $value2) {
					if(!in_array($key2, $fields)) {
						unset($res[$key][$key2]);
					}
				}
			}
		}
		return $res;
	}
	static function checkFields($arr, $params) {
		if(empty($arr)) return false;
		foreach ($params as $key => $val) {
		   $val = array_flip($val);
			if(isset($val['isset'])) {
			   if(isset($val['null'])) {
		    		if($arr[$key] === null) continue;
	    		}
				if(!isset($arr[$key])) return false;
			}
			if(isset($val['required'])) {
				if(empty($arr[$key])) 	return false;
			}
			if(isset($val['numeric'])) {
				if(!is_numeric($arr[$key])) return false;
			}
			if(isset($val['string'])) {
				if(!isset($val[$key])) return false;
				if(is_array($arr[$key]) || is_object($arr[$key])) return false;
			}
		}
		return true;
	}
}
