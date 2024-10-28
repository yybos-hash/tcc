<?php 
require_once "Model.php";

class LoginModel extends Model {
    public function _construct () {
        parent::__construct(); // call Model constructor
    }

    public function loginUser (string $email, string $password) : array|null {
        $result = $this->select("SELECT * FROM tb_user WHERE user_email=:email AND user_password=:pass", [
            "email" => $email,
            "pass" => $password
        ]);

        return $result[0];
    }

    public function registerUser (string $email, string $password, string $name) {
        $this->insert("INSERT INTO tb_user (user_email, user_password, user_name) VALUES (:email, :pass, :nm)", [
            "email" => $email,
            "pass" => $password,
            "nm" => $name
        ]);
    }

    public function userExists (string $email) : array {
        $result = $this->select("SELECT * FROM tb_user WHERE user_email=:email", [
            "email" => $email
        ]);

        return $result;
    }
}
?>