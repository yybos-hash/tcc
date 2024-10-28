<?php 
class MessageHandler extends Handler {    
    public function __construct () {
        parent::__construct();
    }

    // insert the message then select and return the message
    public function postMessage (Message $message, bool $sent = true) : Message|null {
        $postedMessage = $this->database->postMessage($message, $sent);
        $postedMessage->type = "message";
        
        return $postedMessage;
    }
    public function setMessageStatus (Message $message, string $status) {
        $this->database->setMessageStatus($message->hash, $status);
    }
}
?>