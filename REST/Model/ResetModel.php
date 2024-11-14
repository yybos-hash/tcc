<?php 
require_once "Model.php";

class ResetModel extends Model {
    public function _construct () {
        parent::__construct(); // call Model constructor
    }

    public function checkEmail ($email) : string|null {
        $user = $this->select("SELECT tb_user.user_hashed FROM tb_user WHERE tb_user.user_email=:email", [
            "email" => $email
        ]);

        return !empty($user[0]["user_hashed"]);
    }
}
?>