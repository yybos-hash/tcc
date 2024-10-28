<?php 
class Connection {
    protected $user;
    protected $pass;

    protected $pdo;

    public function __construct () {
        $this->user = "root";
        $this->pass = "";

        $this->pdo = new PDO("mysql:host=localhost;dbname=tcc", $this->user, $this->pass);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
?>