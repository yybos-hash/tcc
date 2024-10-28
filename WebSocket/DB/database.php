<?php
require_once "connection.php";

class Database extends Connection {
    private function select (string $sql, array $args) : array {
        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($args);
    
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
        catch (Exception $e) {
            echo "{$e}\n";
            return array();
        }
    }

    private function getUserId (string $userHash) : int {
        $u = $this->select("SELECT user_id FROM tb_user WHERE user_hashed=:h", [
            "h" => $userHash
        ]);

        return intval($u[0]["user_id"]);
    }

    // takes a message object
    public function postMessage (Message $message) : Message|null {
        $senderId = $this->getUserId($message->senderHash);
        $conversationId = $this->select("SELECT conversation_id FROM tb_conversation WHERE conversation_hashed=:h", ["h" => $message->conversationHash])[0]["conversation_id"];

        $this->pdo->beginTransaction();

        $statement = $this->pdo->prepare("INSERT INTO tb_message (message_content, fk_message_sender, fk_message_conversation, message_timestamp) VALUES (:content, :sender, :conversation, :timestamp)");
        $statement->execute([
            "content" => $message->content,
            "sender" => $senderId,
            "conversation" => $conversationId,
            "timestamp" => $message->timestamp
        ]);

        // get the message with the state, hash and timestamp
        $postedMessage = $this->select("SELECT tb_message.message_hashed, tb_message.message_timestamp, tb_message.message_content, tb_message.message_status 
        FROM tb_message
        ORDER BY message_id DESC 
        LIMIT 1", [])[0];

        $postedMessage["fk_message_sender"] = $message->senderHash;
        $postedMessage["fk_message_conversation"] = $message->conversationHash;

        $this->pdo->commit();

        if ($postedMessage === null || count($postedMessage) === 0)
            return null;
    
        return Message::jsonToMessage(json_encode($postedMessage));
    }
    public function setMessageStatus (string $messageHash, string $status) {
        $statement = $this->pdo->prepare("UPDATE tb_message SET message_status=:stts WHERE message_hashed=:h");
        $a = $statement->execute([
            "stts" => $status,
            "h" => $messageHash
        ]);
    }

    public function fetchUser (string $email, string $password) : array {
        $statement = $this->pdo->prepare("SELECT user_hashed, user_name, user_class, user_course FROM tb_user WHERE user_email=:email AND user_password=:pass");
        $statement->execute([
            "email" => $email,
            "pass" => $password
        ]);

        $result = $statement->fetchAll(PDO::FETCH_ASSOC)[0];
        return $result;
    }

    public function fetchUserByHash (string $userHash) : array|null {
        $statement = $this->pdo->prepare("SELECT user_hashed, user_name, user_class, user_course FROM tb_user WHERE user_hashed=:user_hash");
        $statement->execute( ["user_hash" => $userHash] );
        $user = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (count($user) === 0) {
            return null;
        }

        return $user[0];
    }

    public function fetchUserByConversation (string $conversationHash, string $ignoreUser) : string|null {
        // get ignoreUser id
        $ignoreUserId = $this->getUserId($ignoreUser);

        // get conversation id
        $conversationId = $this->select("SELECT conversation_id FROM tb_conversation WHERE conversation_hashed=:h", [ "h" => $conversationHash ])[0]["conversation_id"];

        // get user hash
        $userHashed = $this->select("SELECT tb_user.user_hashed FROM as_conversation_user 
        JOIN tb_user ON tb_user.user_id=as_conversation_user.fk_as_user 
        JOIN tb_conversation ON tb_conversation.conversation_id=as_conversation_user.fk_as_conversation WHERE as_conversation_user.fk_as_conversation=:conversationId 
        AND as_conversation_user.fk_as_user!=:ignoreUser", 
        [
            "conversationId" => $conversationId,
            "ignoreUser" => $ignoreUserId
        ])[0]["user_hashed"];
        
        return $userHashed;
    }

    public function fetchUserFriends (string $userHash) : array|null {
        $friends = $this->select("
            SELECT
                tb_user.user_hashed,
                tb_user.user_name
            FROM tb_friendship JOIN tb_user ON 
            CASE
                WHEN tb_friendship.fk_user1=:h1 THEN tb_friendship.fk_user2
                WHEN tb_friendship.fk_user2=:h2 THEN tb_friendship.fk_user1
            END
            =tb_user.user_id WHERE (tb_friendship.fk_user1=:h3 OR tb_friendship.fk_user2=:h4) 
            AND tb_friendship.friendship_status!='pending'", 
        [
            "h1" => $this->getuserId($userHash),
            "h2" => $this->getuserId($userHash),
            "h3" => $this->getuserId($userHash),
            "h4" => $this->getuserId($userHash)
        ]);


        return $friends;
    }
}
?>