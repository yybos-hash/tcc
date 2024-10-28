<?php 
require_once "Controller.php";

class ChatController extends Controller {
    private $model;
    
    public function __construct () {
        parent::__construct();
        $this->model = new ChatModel();
    }

    public function getView () {
        $jwt = $_COOKIE[Config::$jwtName];

        // if user already has a token, direct him to the chats page, else he goes to the login page
        if ($this->isJwtValid($jwt)) {
            include "View/Chat/index.html";
        }
        else {
            header("Location: " . Config::$route . "/auth");            
        }
    }

    public function getChats () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        
        $decodedJwt = $this->decodeJWT($jwt);
        $chats = $this->model->getChats($decodedJwt->user_hashed);

        $status = $this->SUCCESS_STATUS;
        $status["chats"] = $chats;
        echo json_encode($status);
    }

    public function getMessages () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        $chatHash = $_GET["chat"];

        // need to check if the user has permission to access the messages of this chat
        if (!$this->model->isUserInChat($decodedJwt->user_hashed, $chatHash)) {
            $status = $this->NOT_ALLOWED_STATUS;
            echo json_encode($status);

            return;
        }

        $messages = $this->model->getMessages($chatHash);
    
        $status = $this->SUCCESS_STATUS;
        $status["messages"] = $messages;
        echo json_encode($status);
    }
}
?>