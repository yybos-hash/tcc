<?php 
require_once "Model.php";

class UsersModel extends Model {
    public function __construct () {
        parent::__construct();
    }

    private function getUserId (string $userHash) : int {
        $u = $this->select("SELECT user_id FROM tb_user WHERE user_hashed=:h", [ "h" => $userHash ]);
        return $u[0]["user_id"];
    }

    public function createFriendship (string $user1Hash, string $user2Hash) : bool {
        if ($user1Hash === $user2Hash) {
            return false;
        }

        $user1Id = $this->getUserId($user1Hash);
        $user2Id = $this->getUserId($user2Hash);

        // see if the user1 is already friends with user2
        $friendship = $this->select("SELECT * FROM tb_friendship WHERE (fk_user1=:h1 AND fk_user2=:h2) OR (fk_user1=:h2 AND fk_user2=:h1)", [
            "h1" => $user1Id,
            "h2" => $user2Id
        ]);

        if (count($friendship) !== 0) {
            return false;
        }

        $success = $this->insert("INSERT INTO tb_friendship (fk_user1, fk_user2) VALUES (:h1, :h2)", [
            "h1" => $user1Id,
            "h2" => $user2Id
        ]);

        return $success;
    }
}
?>