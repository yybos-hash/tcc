<?php 
require_once "Controller.php";

class UsersController extends Controller {
    private $model;

    public function __construct () {
        parent::__construct();
        $this->model = new UsersModel();
    }

    public function getView () {
        $jwt = $_COOKIE[Config::$jwtName];

        if ($this->isJwtValid($jwt)) {
            include "View/Users/index.html";
        }
        else {
            header("Location: " . Config::$route . "/auth");            
        }
    }

    public function friendRequest () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        $userHash = filter_input(INPUT_POST, "user", FILTER_SANITIZE_SPECIAL_CHARS);

        $success = $this->model->createFriendship($userHash, $decodedJwt->user_hashed);
        if (!$success) {
            echo json_encode($this->EXISTS_STATUS);
            return;
        }

        echo json_encode($this->SUCCESS_STATUS);
        return;
    }
}
?>