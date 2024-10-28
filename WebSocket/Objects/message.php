<?php 
class Message {
    public string $hash;
    public string $content;

    public string $senderHash;
    public string $conversationHash;

    public string $timestamp;

    public string $type; // this will only be used in the websocket to see if this is a message, status, etc... It is not related to the database at all
    public string $status;

    public function __construct () {
        $this->hash = "";
        $this->content = "";
        $this->senderHash = "";
        $this->conversationHash = "";
        $this->timestamp = "";
        $this->type = "";
        $this->status = "";
    }

    // takes a raw json string
    public static function jsonToMessage (string $jsonStr) : Message {
        $json = json_decode($jsonStr, true);

        $jsonHash = $json["message_hashed"];
        $jsonContent = $json["message_content"];
        $jsonSender = $json["fk_message_sender"];
        $jsonConversation = $json["fk_message_conversation"];
        $jsonTimestamp = $json["message_timestamp"];
        $jsonType = $json["message_type"];
        $jsonStatus = $json["message_status"];

        $message = new Message();
        $message->hash = $jsonHash !== null ? $jsonHash : "";
        $message->content = $jsonContent !== null ? strip_tags($jsonContent) : "";
        $message->senderHash = $jsonSender !== null ? $jsonSender : "";
        $message->conversationHash = $jsonConversation !== null ? $jsonConversation : "";
        $message->timestamp = $jsonTimestamp !== null ? $jsonTimestamp : "";
        $message->type = $jsonType !== null ? $jsonType : "";
        $message->status = $jsonStatus !== null ? $jsonStatus : "";

        return $message;
    }
    // returns raw json string
    public static function messageToJson (Message $message) : string {
        $json = array();

        $json["message_hashed"] = $message->hash;
        $json["message_content"] = strip_tags($message->content);
        $json["fk_message_sender"] = $message->senderHash;
        $json["fk_message_conversation"] = $message->conversationHash;
        $json["message_timestamp"] = $message->timestamp;
        $json["message_type"] = $message->type;
        $json["message_status"] = $message->status;

        return json_encode($json);
    }
}
?>