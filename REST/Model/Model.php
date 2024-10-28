<?php 
class Model {
    protected $pdo;

    public function __construct () {
        // Database credentials
        $server = "localhost";
        $username = "root";
        $password = "";
        $database = "tcc";

        try {
            // Create a connection
            $this->pdo = new PDO("mysql:host=$server;dbname=$database", $username, $password);
        
            // Set the PDO error mode to exception
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection failed: ".$e->getMessage();
        }
    }

    protected function select (string $sql, array $args) : array {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($args);

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
    protected function insert (string $sql, array $args) : bool {
        $statement = $this->pdo->prepare($sql);
        return $statement->execute($args);
    }
    protected function update (string $sql, array $args) : bool {
        $statement = $this->pdo->prepare($sql);
        return $statement->execute($args);
    }
    protected function delete (string $sql, array $args) : bool {
        $statement = $this->pdo->prepare($sql);
        return $statement->execute($args);
    }

    public function fetchUser (string $userHash) : array|null {
        $user = $this->select("SELECT user_hashed FROM tb_user WHERE user_hashed=:user_hash", [
            "user_hash" => $userHash
        ]);

        if (count($user) === 0) {
            return null;
        }

        return $user;
    }
}
?>