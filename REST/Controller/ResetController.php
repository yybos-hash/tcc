<?php 
require_once "Controller.php";

class ResetController extends Controller {
    private $model;

    public function __construct () {
        parent::__construct();
    
        $this->model = new ResetModel();
    }
    
    public function getView () {
        include "View/Reset/index.html";
    }

    public function resetRequest () {
        $email = trim(filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL));
        if (empty($email)) {
            return $this->ERROR_STATUS;
        }

        if (!$this->model->checkEmail($email)) {
            return $this->NOTFOUND_STATUS;
        }

        mail($email, "Reset de Senha", "Body", "From: " . Config::$email);
    }
}
?>