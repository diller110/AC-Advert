<?php
$f3=require('lib/base.php');
error_reporting(E_ALL);
$f3->config('app/config.ini');
$f3->set('db', new DB());

$f3->route('GET @main: /','Main->index');
$f3->route('GET @words: /words','Main->words');
$f3->route('GET @ads: /ads','Main->ads');
$f3->route('GET|POST @login: /login', 'Main->login');

$f3->route('GET /ex/rcon/@id', 'Ex->rcon');
$f3->route('GET /ex/server/@id','Ex->server');
$f3->route('POST /ex/server','Ex->serverPost');
$f3->route('GET /ex/server/@id/delete','Ex->serverDelete');
$f3->route('GET /ex/words/@id','Ex->words');
$f3->route('POST /ex/words','Ex->wordsPost');
$f3->route('GET /ex/words/@id/delete','Ex->wordsDelete');
$f3->route('GET /ex/ads/@id','Ex->ads');
$f3->route('POST /ex/ads','Ex->adsPost');
$f3->route('GET /ex/ads/@id/delete','Ex->adsDelete');

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
