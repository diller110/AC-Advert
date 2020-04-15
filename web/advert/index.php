<?php
$f3=require('lib/base.php');
$f3->config('app/config.ini');
$f3->set('AUTOLOAD', "app/");
$f3->set('CACHE', TRUE);
$f3->set('db', new DB());
$f3->set('skey', 'SESSION.ac2.'); // SESSION namespace
$f3->set('user', new User());
$f3->set('V.scriptExecutionTime', microtime(true));
//$f3->set('ONERROR', 'Main->onError');
\Template::instance()->filter('encode','\Main::filterEncode');

$f3->set('PREFIX', 'T.');
$f3->set('LOCALES','view/lang/');
$f3->set('LANGUAGE', $f3->exists('lang')?$f3->get('lang'):'ru');

/* ROUTES */
$f3->route('GET @main: /','Main->index');
$f3->route('GET @words: /words','Main->words');
$f3->route('GET @ads: /dot','Main->ads');
$f3->route('GET @login: /login', 'Main->login');
$f3->route('POST /api/login', 'User->loginPost');
$f3->route('GET /logout', 'Main->logout');
$f3->route('GET /offer', 'Main->offer');

$f3->route('GET /api/server/get', 'Server->getList');
$f3->route('GET /api/server/delete/@srv_id', 'Server->delete');
$f3->route('POST /api/server/save', 'Server->save');
$f3->route('POST /api/server/save/field', 'Server->saveField');
$f3->route('GET /api/server/update/@srv_id', 'Server->update');
$f3->route('POST /api/server/hotmsg/@srv_id', 'Server->hotMsg');

$f3->route('GET /api/words/get', 'Words->getList');
$f3->route('GET /api/words/delete/@word_id', 'Words->delete');
$f3->route('POST /api/words/save', 'Words->save');
$f3->route('POST /api/words/save/field', 'Words->saveField');

$f3->route('GET /api/dot/get', 'Ads->getList');
$f3->route('GET /api/dot/delete/@adv_id', 'Ads->delete');
$f3->route('POST /api/dot/save', 'Ads->save');

$f3->route('GET /api/auth', 'Api->auth');
$f3->route('GET /api/get', 'Api->get');
$f3->route('GET /api/test', 'Api->test');
$f3->route('GET /api/hotmsg/@msg_id', 'Api->hotmsg');

$f3->route('GET|POST /app', function($f3) { $f3->reroute('@main'); });
$f3->route('GET|POST /app/*', function($f3) { $f3->reroute('@main'); });
$f3->route('GET /css/@file', function($f3) {
	header('Content-Type: text/css');
	echo $f3->read($f3->get('UI').'css/'.$f3->get('PARAMS.file'));
});
$f3->route('GET /js/@file', function($f3) {
	header('Content-Type: application/javascript');
	echo $f3->read($f3->get('UI').'js/'.$f3->get('PARAMS.file'));
});
$f3->route('GET /img/*', function($f3) {
	header('Pragma: public');
	header('Cache-Control: max-age=86400');
	header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
	header('Content-Type: image/png');
	(new Image('img/'.$f3->get('PARAMS.*')))->render();
});

$f3->run();
