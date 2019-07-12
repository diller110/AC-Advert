<?
/*
    DataBaseBridge class, if for centralized work with database, wrap some boring staff.
    All incoming data, will check, escape.
    All exception will catch.
    You just need to get the result of your operation in format below, and to operate it.

    Array() = {
        'status' => 'true', // false
        'reason' => 'Description of failure' // Not enought access, etc.
        'res' => Array('some data here'); // if status true, else null
    };
*/

class DB {
    protected $db = null;
    protected $f3 = null;
    protected $t = '';

    function __construct() {
        global $f3;
        $this->db = new DB\SQL('mysql:host='.$f3->get('db_host').';port=3306;dbname='.$f3->get('db_name'),	$f3->get('db_user'), $f3->get('db_pass'));
    }
    /* List, Get, Edit, Add, Delete routes */
    public function list2(...$arguments) {
        if(is_callable(array($this, '_'.$this->t.'List'))) {
            $res = $this->{'_'.$this->t.'List'}($arguments);
        } else $res = $this->fpack('Unknown List action');
        $this->t = ''; return $res;
    }
    public function get(...$arguments) {
        if(is_callable(array($this, '_'.$this->t.'Get'))) {
            $res = $this->{'_'.$this->t.'Get'}($arguments);
        } else $res = $this->fpack('Unknown Get action');
        $this->t = ''; return $res;
    }
    public function edit(...$arguments) {
        if(is_callable(array($this, '_'.$this->t.'Edit'))) {
            $res = $this->{'_'.$this->t.'Edit'}($arguments);
        } else $res = $this->fpack('Unknown Edit action');
        $this->t = ''; return $res;
    }
    public function add(...$arguments) {
        if(is_callable(array($this, '_'.$this->t.'Add'))) {
            $res = $this->{'_'.$this->t.'Add'}($arguments);
        } else $res = $this->fpack('Unknown Add action');
        $this->t = ''; return $res;
    }
    public function delete(...$arguments) {
        if(is_callable(array($this, '_'.$this->t.'Delete'))) {
            $res = $this->{'_'.$this->t.'Delete'}($arguments);
        } else $res = $this->fpack('Unknown Delete action');
        $this->t = ''; return $res;
    }
    public function getTable($table) { return new DB\SQL\Mapper($this->db, $table); }
    protected function pack($result) { return array('status' => true, 'res' => $result); }
    protected function fpack($reason) { return array('status' => false, 'reason' => $reason, 'res' => null); }

    /* Objects */

    function server() {
      $this->t = 'server';
      return $this;
    }
    private function _serverList(...$arguments) {
        $res = $this->getTable('servers')->find();
        if(empty($res)) {
            return $this->fpack('No servers');
        }
        return $this->pack($res);
    }
    private function _serverGet(...$data) {
      $res = $this->getTable('servers')->load(array('srv_id=?', $data[0][0]));
      if(empty($res)) {
          return $this->fpack('No server found');
      }
      return $this->pack($res);
    }
    private function _serverAdd(...$data) {
      $server = $this->getTable('servers');
      if($server) {
          unset($data[0][0]['srv_id']);
          $server->copyFrom($data[0][0]);
          $server->save();

          return $this->pack($server);
      }
    }
    private function _serverDelete(...$data) {
      $res = $this->getTable('servers')->load(array('srv_id=?', $data[0][0]));
      if(empty($res)) {
          return $this->fpack('No server found');
      }
      $res->erase();
    }
    private function _serverEdit(...$data) {
      $res = $this->getTable('servers')->load(array('srv_id=?', $data[0][0]['srv_id']));
      if(empty($res)) {
          return $this->fpack('No server found');
      }
      $res->copyFrom($data[0][0]);
      $res->save();
      return $this->pack($res);
    }

    function words() {
      $this->t = 'words';
      return $this;
    }
    private function _wordsList(...$arguments) {
        $res = $this->getTable('magic_words')->find();
        if(empty($res)) {
            return $this->fpack('No words');
        }
        return $this->pack($res);
    }
    private function _wordsGet(...$data) {
      $res = $this->getTable('magic_words')->load(array('word_id=?', $data[0][0]));
      if(empty($res)) {
          return $this->fpack('No word found');
      }
      return $this->pack($res);
    }
    private function _wordsAdd(...$data) {
      $res = $this->getTable('magic_words');
      if($res) {
          unset($data[0][0]['word_id']);
          $res->copyFrom($data[0][0]);
          $res->save();
          return $this->pack($res);
      }
    }
    private function _wordsDelete(...$data) {
      $res = $this->getTable('magic_words')->load(array('word_id=?', $data[0][0]));
      if(empty($res)) {
          return $this->fpack('No word found');
      }
      $res->erase();
    }
    private function _wordsEdit(...$data) {
      $res = $this->getTable('magic_words')->load(array('word_id=?', $data[0][0]['word_id']));
      if(empty($res)) {
          return $this->fpack('No word found');
      }
      $res->copyFrom($data[0][0]);
      $res->save();
      return $this->pack($res);
    }

    function ads() {
      $this->t = 'ads';
      return $this;
    }
    private function _adsList(...$arguments) {
        $res = $this->getTable('advert')->find([''], ['order'=>'order']);
        if(empty($res)) {
            return $this->fpack('No ads');
        }
        return $this->pack($res);
    }
    public function adsServers($adv_id) {
        $res = $this->db->exec('SELECT * FROM servers WHERE srv_id IN (select srv_id from server_ads where adv_id=?)', $adv_id);
        if(empty($res)) {
            return $this->fpack('Not found');
        }
        return $this->pack($res);
    }
    public function adsHud($adv_id) {
      $res = $this->getTable('hud_style')->load(['adv_id=?', $adv_id]);
      if(empty($res)) {
          return $this->fpack('Not found');
      }
      return $this->pack($res);
    }
    private function _adsGet(...$data) {
      $res = $this->getTable('advert')->load(array('adv_id=?', $data[0][0]));
      if(empty($res)) {
          return $this->fpack('No ads found');
      }
      return $this->pack($res);
    }

    private function _adsAdd(...$data) {
      $res = $this->getTable('advert');
      if($res) {
          unset($data[0][0]['adv_id']);
          $res->copyFrom($data[0][0]);
          $res->save();

          return $this->pack($res);
      }
    }
    private function _adsDelete(...$data) {
      $res = $this->getTable('advert')->load(array('adv_id=?', $data[0][0]));
      if(empty($res)) {
          return $this->fpack('No ads found');
      }
      $res->erase();
    }
    private function _adsEdit(...$data) {
      $res = $this->getTable('advert')->load(array('adv_id=?', $data[0][0]['adv_id']));
      if(empty($res)) {
          return $this->fpack('No ads found');
      }
      $res->copyFrom($data[0][0]);
      $res->save();

      return $this->pack($res);
    }

}
