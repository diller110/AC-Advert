<?php
class DB {
    public $db = null;
    function __construct() {
        global $f3;
        $this->db = new DB\SQL('mysql:host='.$f3->get('db_host').';port=3306;dbname='.$f3->get('db_name'),	$f3->get('db_user'), $f3->get('db_pass'));
    }
    public function getTable($table) { global $f3; return new DB\SQL\Mapper($this->db, $f3->get('db_prefix').$table); }
}
