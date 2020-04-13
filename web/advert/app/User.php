<?php
require_once __DIR__.'/Libs/Validator.php';
use Valitron\Validator as V;
V::langDir(__DIR__.'/Libs/Validator/lang'); // always set langDir before lang.
V::lang('ru');

class User {
	public $data = null;
	function __construct() {
		global $f3;
		if(!$this->isLogged()) return;
		$this->data = $f3->get('db')->getTable('users')->load(['user_id=?', $this->userId()]);
		if(!$this->data) {
			$f3->clear($f3->get('skey').'user_id');
			return;
		}
		$this->data = $this->data->cast();
	}
	function userId() {
		global $f3;
		return $f3->get($f3->get('skey').'user_id');
	}
	function isLogged() {
		global $f3;
		if($f3->exists($f3->get('skey').'user_id')) return true;
		return false;
	}

	static function GetUserData($id) {
		global $f3;
		return $f3->get('db')->getTable('users')->load(['user_id=?', $id]);
	}
	static function register($req) {
		global $f3;
		if($f3->get('user')->isLogged()) {
			die(json_encode([
				'res' => -1,
				'text' => '<span class="text-red-500">Вы уже зарегистрированы!</span>'
			]));
		}
		if($f3->get('register_allowed') != 1) {
			die(json_encode([
				'res' => -1,
				'text' => '<span class="text-red-500">Администратор отключил регистрацию.</span>'
			]));
		}
		$user = $f3->get('db')->getTable('users')->load(['login=?', $req['login']]);
		if($user) {
			die(json_encode([
				'res' => -1,
				'text' => '<span class="text-red-500">Пользователь с таким логином уже существует!</span>'
			]));
		}
		$user = $f3->get('db')->getTable('users');
		$user->reset();
		$req = array_map('trim', $req);
		$v = new V($req);
	   $v->rule('required', ['email']);
	   if(!$v->validate()) {
		  die(json_encode([
			  'res' => -2,
			  'text' => '<span class="text-red-500">Укажите email</span>'
		  ]));
	   }
	   $v->rule('email', ['email']);
	   if(!$v->validate()) {
		  die(json_encode([
			  'res' => -2,
			  'text' => '<span class="text-red-500">Укажите корректный email</span>'
		  ]));
	   }

		$user->login = $req['login'];
		$user->password = password_hash($req['password'], PASSWORD_DEFAULT);
		$user->email = $req['email'];
		$user->created = date("Y-m-d H:i:s", time());
		$user->token = bin2hex(random_bytes(14));
		$user->iv = User::createIv();
		$user->save();
		$f3->set($f3->get('skey').'user_id', $user->user_id);
		die(json_encode(['res' => 1]));
	}
	function logout() {
		global $f3;
		$f3->clear(rtrim($f3->get('skey'), '.'));
	}
  function login($req) {
	  global $f3;
	  if($f3->get('user')->isLogged()) {
		  die(json_encode([
			  'res' => -1,
			  'text' => '<span class="text-red-500">Вы уже авторизованы!</span>'
		  ]));
	  }
	  $user = $f3->get('db')->getTable('users')->load(['login=?', $req['login']]);
	  if(!$user) {
		  die(json_encode([
			  'res' => -1,
			  'text' => '<span class="text-red-500">Пользователя с таким логином не существует!</span>'
		  ]));
	  }
	  if(!password_verify($req['password'], $user->password)) {
		  die(json_encode([
			  'res' => -1,
			  'text' => '<span class="text-red-500">Пароль неверен!</span>'
		  ]));
	  }
	  if(empty($user->email)) {
		  $v = new V($req);
		  $v->rule('required', ['email']);
		  if(!$v->validate()) {
			 die(json_encode([
   			 'res' => -2,
   			 'text' => '<span class="text-red-500">Обновите свой email</span>'
   		 ]));
		  }
		  $v->rule('email', ['email']);
		  if(!$v->validate()) {
			 die(json_encode([
   			 'res' => -2,
   			 'text' => '<span class="text-red-500">Укажите корректный email</span>'
   		 ]));
		  }
			$user->email = $req['email'];
			$user->iv = User::createIv();
			$user->save();
			$f3->set($f3->get('skey').'user_id', $user->user_id);
			(new Server())->encryptRcons($user->iv);
			die(json_encode(['res' => 1]));
	  }
	  $f3->set($f3->get('skey').'user_id', $user->user_id);
	  die(json_encode(['res' => 1]));
  }
  function loginPost($f3, $params) {
	  if($f3->get('user')->isLogged()) {
		  die('0');
	  }
	  $req = json_decode($f3->get('BODY'), true);
	  if($req == null) die('0');
	  $req = $req['data'];
	  if(!isset($req['login']) || !isset($req['password'])) {
		  die('0');
	  }
	  if(strlen($req['login']) < 3 || strlen($res['login']) > 45 || strlen($req['password']) < 3 || strlen($res['password']) > 64) {
		  die('0');
	  }
	  if(!isset($req['action'])) {
		  die('0');
	  }

	  switch($req['action']) {
		  case 'login':
		  	  $this->login($req);
			  break;
		  case 'register':
		  	  $this->register($req);
			  break;
		  default:
			  die('0');
			  break;
	  }
  }
  static function createIv() {
	  global $f3;
	  $ivlen = openssl_cipher_iv_length($f3->get('crypto_cipher'));
	  return substr(bin2hex(random_bytes($ivlen)), 0, $ivlen);
  }
  static function encryptUserData($iv, $value) {
	  global $f3;
	  return openssl_encrypt($value, $f3->get('crypto_cipher'), $f3->get('crypto_key'), 0, $iv);
  }
  static function decryptUserData($iv, $value) {
	  global $f3;
	  return openssl_decrypt($value, $f3->get('crypto_cipher'), $f3->get('crypto_key'), 0, $iv);
  }
}
