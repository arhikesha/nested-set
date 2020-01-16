<?php
class DbConn{
    const DNS = 'mysql:dbname=book;host=127.0.0.1';
    const USER = 'root';
    const PASSWORD = 'password';
    private $_db;
    static private $_instance;

    private function __construct(){
        try {
            $this->_db = new PDO(self::DNS, self::USER, self::PASSWORD,
                [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        }catch (PDOException $e) {
            echo 'Подключение не удалось: ' . $e->getMessage();
        }
    }
    private function __clone(){}
    static function getInstance(){
        if(self::$_instance == null){
            self::$_instance = new DbConn();
        }
        return self::$_instance;
    }
    public function getDb(){
        return $this->_db;
    }
}
