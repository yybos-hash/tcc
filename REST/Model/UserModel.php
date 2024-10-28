<?php 
require_once "Model.php";

class UserModel extends Model {
    public function __construct () {
        parent::__construct();
    }

    private function getUserId (string $userHash) : int {
        $u = $this->select("SELECT user_id FROM tb_user WHERE user_hashed=:h", [
            "h" => $userHash
        ]);

        return $u[0]["user_id"];
    }

    public function getUser (string $userHash) : array|null {
        $user = $this->select("SELECT user_hashed, user_email, user_class, user_name, user_course, user_description, user_join FROM tb_user WHERE user_hashed=:user_hash", [
            "user_hash" => $userHash
        ]);

        if (count($user) === 0) {
            return null;
        }

        $user[0]["pfp"] = $this->getUserPicture($userHash);
        $user[0]["background"] = $this->getUserBackground($userHash);

        return $user[0];
    }
    public function getUserInfo (string $userHash) : array|null {
        $user = $this->select("SELECT user_class, user_name, user_course, user_description, user_hashed, user_join FROM tb_user WHERE user_hashed=:user_hash", [
            "user_hash" => $userHash
        ]);

        if (count($user) === 0) {
            return null;
        }

        $user[0]["pfp"] = $this->getUserPicture($userHash);
        $user[0]["background"] = $this->getUserBackground($userHash);

        return $user[0];
    }
    public function searchUser (string $userName) : array {
        $sql = "SELECT user_hashed, user_name, user_class, user_course FROM tb_user WHERE user_name LIKE :u_name OR user_class LIKE :u_name OR user_course LIKE :u_name";
        $args = [
            "u_name" => "%" . $userName . "%"
        ];    

        $users = $this->select($sql, $args);

        if (count($users) !== 0) {
            for ($i = 0; $i < count($users); $i++) {
                $users[$i]["pfp"] = $this->getUserPicture($users[$i]["user_hashed"]);
            }
        }

        return $users;
    }

    public function getFriends (string $userHash) : array|null {
        $friends = $this->select("
            SELECT 
                tb_friendship.friendship_date,
                tb_friendship.friendship_hashed,
                tb_friendship.friendship_status,
                tb_user.user_hashed,
                tb_user.user_description,
                tb_user.user_name,
                tb_user.user_course,
                tb_user.user_class
            FROM tb_friendship JOIN tb_user ON 
            CASE
                WHEN tb_friendship.fk_user1=:h1 THEN tb_friendship.fk_user2
                WHEN tb_friendship.fk_user2=:h1 THEN tb_friendship.fk_user1
            END
            =tb_user.user_id WHERE (tb_friendship.fk_user1=:h1 OR tb_friendship.fk_user2=:h1) 
            AND tb_friendship.friendship_status!='pending'", 
        [
            "h1" => $this->getuserId($userHash)
        ]);

        if (count($friends) === 0) {
            return null;
        }

        for ($i = 0; $i < count($friends); $i++) {
            $friends[$i]["pfp"] = $this->getUserPicture($friends[$i]["user_hashed"]);            
        }

        return $friends;
    }
    public function getPendingFriends (string $userHash) : array|null {
        $pending = $this->select("
            SELECT
                tb_friendship.friendship_date,
                tb_friendship.friendship_hashed,
                tb_friendship.friendship_status,
                tb_user.user_hashed,
                tb_user.user_name,
                tb_user.user_course,
                tb_user.user_class
            FROM tb_friendship
            JOIN tb_user ON tb_user.user_id=tb_friendship.fk_user2
            WHERE tb_friendship.friendship_status='pending' AND tb_friendship.fk_user1=:h
        ",
        [
            "h" => $this->getUserId($userHash)
        ]);
        // I did the 'tb_friendship.fk_user2=:h' because fk_user2 is the user who received the request

        if (count($pending) === 0) {
            return null;
        }

        for ($i = 0; $i < count($pending); $i++) {
            $pending[$i]["pfp"] = $this->getUserPicture($pending[$i]["user_hashed"]);            
        }

        return $pending;
    }
    public function acceptFriendship (string $userHash, string $friendshipHash) : bool {
        $userId = $this->getUserId($userHash);

        // see if the user has this friendship
        $friendship = $this->select("SELECT fk_user1, fk_user2 FROM tb_friendship WHERE friendship_hashed=:id", [
            "id" => $friendshipHash
        ]);

        if (count($friendship) === 0) {
            return false;
        }

        $friendship = $friendship[0];
        if ($friendship["fk_user1"] === $userId || $friendship["fk_user2"] === $userId) {
            $success = $this->update("UPDATE tb_friendship SET friendship_status='accepted' WHERE friendship_hashed=:id", [
                "id" => $friendshipHash,
            ]);

            $this->createChat($friendship["fk_user1"], $friendship["fk_user2"]);
    
            return $success;        
        }

        return false;
    }

    public function updateUserInfo (string $userHash, string $email, string $name, string $class, string $course, string $desc) : array|null {
        $sql = "UPDATE tb_user SET user_email=:email, user_name=:username, user_class=:class, user_course=:course, user_description=:d WHERE user_hashed=:h";
        $vars = [
            "email" => $email,
            "username" => $name,
            "class" => $class,
            "course" => $course,
            "d" => $desc,
            "h" => $userHash
        ];

        $success = $this->update($sql, $vars);
        
        if ($success)
            return $this->getUser($userHash);
        else
            return null;
    }

    public function setUserPicture (string $userHash, string $imageData) : bool {
        $path = "Model/profiles/".$userHash."/images";

        // The directory doesn't exist, create it
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        // saves the image to profiles/{user_id}/images
        $image = base64_decode($imageData);
        $imageFile = imagecreatefromstring($image);

        // Save the image as JPEG
        imagejpeg($imageFile, $path."/pfp.jpeg", 100); // quality has already been set in the squishImage function

        // Free up memory
        imagedestroy($imageFile);

        return true;
    }
    public function getUserPicture (string $userHash) : string {
        $path = "Model/profiles/".$userHash."/images";

        // The directory doesn't exist, create it
        if (!is_dir($path) || !file_exists($path . "/pfp.jpeg")) {
            return base64_encode(file_get_contents("Model/profiles/no_profile_icon.jpeg"));
        }

        return base64_encode(file_get_contents($path."/pfp.jpeg"));
    }

    public function setUserBackground (string $userHash, string $imageData) : bool {
        $path = "Model/profiles/".$userHash."/images";

        // The directory doesn't exist, create it
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        // saves the image to profiles/{user_id}/images
        $image = base64_decode($imageData);
        $imageFile = imagecreatefromstring($image);

        // Save the image as JPEG
        imagejpeg($imageFile, $path . "/background.jpeg", 100); // quality has already been set in the squishImage function

        // Free up memory
        imagedestroy($imageFile);

        return true;
    }
    public function getUserBackground (string $userHash) : string {
        $path = "Model/profiles/".$userHash."/images";

        // The directory doesn't exist, create it
        if (!is_dir($path) || !file_exists($path . "/background.jpeg")) {
            return base64_encode(file_get_contents("Model/profiles/no_background.jpg"));
        }

        return base64_encode(file_get_contents($path . "/background.jpeg"));
    }

    public function deleteUser (string $userHash) : bool {
        $delete = $this->delete("DELETE FROM tb_user WHERE user_hashed=:h", [
            "h" => $userHash
        ]);

        $path = "Model/profiles/".$userHash;
        if (is_dir($path)) {
            $files = glob($path . "/images/*");

            foreach ($files as $file) {
                unlink($file);
            }
            
            if (!rmdir($path . "/images") | !rmdir($path)) {
                return false;
            }
        }

        return $delete;
    }

    // this will be called right after the acceptFriendship
    private function createChat (int $user1Id, int $user2Id) : bool {
        // avoid concurrency with transaction 👌
        $this->pdo->beginTransaction();

        $this->insert("INSERT INTO tb_conversation VALUES ()", []);
        $conversationId = $this->select("SELECT conversation_id FROM tb_conversation ORDER BY conversation_id DESC LIMIT 1", [])[0]["conversation_id"];

        if ($conversationId === null) {
            return false;
        }

        $this->insert("INSERT INTO as_conversation_user (fk_as_conversation, fk_as_user) VALUES (:c, :u)", [
            "c" => $conversationId,
            "u" => $user1Id
        ]);
    
        $this->insert("INSERT INTO as_conversation_user (fk_as_conversation, fk_as_user) VALUES (:c, :u)", [
            "c" => $conversationId,
            "u" => $user2Id
        ]);

        $this->pdo->commit();

        return true;
    }
}
?>