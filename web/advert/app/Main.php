<?php
use Jumbojett\OpenIDConnectClient;
class Main {
	function beforeRoute($f3) {
		if($f3->get('PARAMS')[0] != '/login') {
			if(!$f3->get('SESSION.logged')) {
				$f3->reroute('@login');
			}
		}
	}
	function login($f3, $params) {
		Main::layout();
		$f3->set('V.page', 'login');
		$f3->set('content', 'cell/login.htm');
		if($f3->exists('POST.pass')) {
			if($f3->get('POST.pass') == '0') {
				$f3->set('SESSION.logged', false);
			} else if($f3->get('POST.pass') == $f3->get('pass')) {
				$f3->set('SESSION.logged', true);
				$f3->reroute('@main');
			}
		}
		Main::render();
	}
	function index($f3, $params) {
		Main::layout();
		$f3->set('V.page', 'servers');
		$f3->set('V.button', [
			'text'=>$f3->get('T.add_server'),
			'href'=>'#btn_add'
		]);
		$f3->set('content', 'cell/servers.htm');
		$servers = $f3->get('db')->server()->list2();
		if($servers['status']) {
			$f3->set('servers', $servers['res']);
		}
		Main::render();
	}
	function words($f3, $params) {
		Main::layout();
		$f3->set('V.page', 'words');
		$f3->set('V.button', [
			'text'=>$f3->get('T.add_magic_word'),
			'href'=>'#btn_add'
		]);
		$f3->set('content', 'cell/words.htm');
		$servers = $f3->get('db')->words()->list2();
		if($servers['status']) {
			$f3->set('words', $servers['res']);
		}
		Main::render();
	}
	function ads($f3, $params) {
		Main::layout();
		$f3->set('V.page', 'ads');
		$f3->set('V.wider', 1);
		$f3->set('V.button', [
			'text'=>$f3->get('T.add_ads'),
			'href'=>'#btn_add'
		]);
		$f3->set('content', 'cell/ads.htm');

		$servers = $f3->get('db')->server()->list2();
		if($servers['status']) {
			$f3->set('servers', $servers['res']);
		}
		$ads = $f3->get('db')->ads()->list2();
		if($ads['status']) {
			$ads = $ads['res'];
			foreach($ads as $key => $value) {
				$srvs = $f3->get('db')->adsServers($ads[$key]->adv_id);
				if($srvs['status']) {
					$ads[$key]['servers'] = $srvs['res'];
				}
				if($ads[$key]->msg_type == 2) {
					$hud = $f3->get('db')->adsHud($ads[$key]->adv_id);
					if($hud['status']) {
						$ads[$key]['hud'] = $hud['res'];

						$colors = explode(" ", $hud['res']['color1']);
						if(count($colors) == 4) {
							$ads[$key]['msg_text'] = '<span style="font-weight: bold; font-size: 20px; color: rgba('.$colors[0].','.$colors[1].','.$colors[2].','.number_format($colors[3]/255, 2, '.', '').')">'.$ads[$key]['msg_text'];
						}

					}
				}
			}
			$f3->set('ads', $ads);
		}
		Main::render();
	}

	static function layout() {
		global $f3;

		$f3->set('styles', array());
		$f3->push('styles', 'reset.css');
		$f3->push('styles', 'content-tools.min.css');
		$f3->push('styles', 'spectre.min.css');
		$f3->push('styles', "styles");
		if($f3->get('dark_mode')) $f3->push('styles', 'styles.dark');

		$f3->set('theme_color', '#E56E41');

		$f3->set('style_variables', array(
			"color-primary" => "#143be6", // #314AA3 //DFB4A0 //DBCF5E //E4A035
			"color-ancent" => "#e4a035",
			"color-text-primary" => "#333"
		));
	}
	static function render() {
		global $f3;
		if($f3->get('SESSION.logged') && $f3->exists('GET.cs')) {
			try {
				require_once __DIR__ . '/Libs/less.php/Less.php';
			  $less = new Less_Parser(array( 'sourceMap' => true, 'compress'=>true ));
				foreach ($f3->get('styles') as $key => $value) {
					if(strpos($value, 'styles')===FALSE) {
						continue;
					}
					$less = new Less_Parser(array( 'sourceMap' => true, 'compress'=>true ));
					$less->parseFile(dirname(__DIR__).'/view/http/css/'.$value.'.less', dirname(__DIR__).'/view/http/css/');
					$less->ModifyVars($f3->get('style_variables'));
					file_put_contents(dirname(__DIR__).'/view/http/css/'.$value.'.css',  $less->getCss());
				}
			} catch(Exception $e){
			    echo $e->getMessage();
			}
		}
		echo \Template::instance()->render('layout.htm');
	}
}
