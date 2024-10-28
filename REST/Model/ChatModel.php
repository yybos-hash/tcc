<?php 
require_once "Model.php";

class ChatModel extends Model {
    public function _construct () {
        parent::__construct(); // call Model constructor
    }

    // gets all the chats the user is involved in
    public function getChats (string $userHash) : array {
        $userId = $this->select("SELECT user_id FROM tb_user WHERE user_hashed=:id", [
            "id" => $userHash
        ])[0]["user_id"];

        $chats = $this->select("SELECT 
            tb_conversation.conversation_hashed, tb_conversation.conversation_created, tb_user.user_hashed, tb_user.user_name, tb_user.user_class, tb_user.user_course, tb_user.user_description
            FROM tb_conversation
            JOIN as_conversation_user ON tb_conversation.conversation_id=as_conversation_user.fk_as_conversation
            JOIN tb_user ON as_conversation_user.fk_as_user=tb_user.user_id
            WHERE tb_conversation.conversation_id IN (
                SELECT tb_conversation.conversation_id
                FROM tb_conversation
                JOIN as_conversation_user ON tb_conversation.conversation_id=as_conversation_user.fk_as_conversation
                WHERE as_conversation_user.fk_as_user=:id
            )
            AND tb_user.user_id!=:id", [
            "id" => $userId
        ]);

        return $chats;
    }

    public function getMessages (string $chatHash) : array {
        $chatId = $this->select("SELECT conversation_id FROM tb_conversation WHERE conversation_hashed=:h", ["h" => $chatHash])[0]["conversation_id"];

        $messages = $this->select("SELECT 
        tb_message.message_hashed, 
        tb_message.message_content, 
        tb_user.user_hashed AS fk_message_sender,
        tb_conversation.conversation_hashed AS fk_message_conversation, 
        tb_user.user_name,
        tb_message.message_timestamp,
        tb_message.message_status 
        FROM tb_message
        JOIN tb_user ON tb_user.user_id=tb_message.fk_message_sender 
        JOIN tb_conversation ON tb_conversation.conversation_id=tb_message.fk_message_conversation
        WHERE fk_message_conversation=:id", [
            "id" => $chatId
        ]);

        return $messages;
    }

    public function isUserInChat (string $userHash, string $chatHash) : bool {
        // get user id
        $userId = $this->select("SELECT user_id FROM tb_user WHERE user_hashed=:id", [
            "id" => $userHash
        ])[0]["user_id"];

        if ($userId === null)
            return false;

        // get chat id
        $chatId = $this->select("SELECT conversation_id FROM tb_conversation WHERE conversation_hashed=:id", [
            "id" => $chatHash
        ])[0]["conversation_id"];

        if ($chatId === null)
            return false;

        // get chats
        $chats = $this->select("SELECT fk_as_conversation FROM as_conversation_user WHERE fk_as_user=:id AND fk_as_conversation=:convId", [
            "id" => $userId,
            "convId" => $chatId
        ]);

        return !(count($chats) === 0);
    }
}
?>