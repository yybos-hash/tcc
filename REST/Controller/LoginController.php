<?php 
require_once "Controller.php";

class LoginController extends Controller {
    private $model;

    public function __construct () {
        parent::__construct();

        $this->model = new LoginModel();
    }

    public function getView () {
        include "View/Login/index.html";
    }

    public function login () {
        $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
        $password = $_POST["password"];

        if (!isset($email) || !isset($password)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        $password = hash("sha256", $password);

        if (!$this->userExists($email)) {
            echo json_encode($this->NOTFOUND_STATUS);
            return;
        }

        $user = $this->model->loginUser($email, $password);
        
        // wrong password
        if ($user === null) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        $userJWT = $this->encodeJWT($user);

        // need to create a cookie here for the user. User logs in, I send token through cookie, user sends token again, user is verified
        //                                         hehehe travessuras (1 ano)
        setcookie(Config::$jwtName, $userJWT, time() + (365 * 24 * 60 * 60), "/", "", Config::$isHttps, true);

        // approved 💯
        $status = $this->SUCCESS_STATUS;
        $status["redirect"] = Config::$route . "/chat";
        echo json_encode($status);
    }

    public function register () {
        // if email is invalid
        if (!filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL)) {
            echo json_encode($this->INVALID_EMAIL_STATUS);
            return;
        }

        $name = filter_input(INPUT_POST, "name", FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
        $password = $_POST["password"];
    
        if (!isset($email) || !isset($password) || !isset($name)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        if ($email == "" || $password == "" || $name == "") {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        // you didnt expect me to just store the password in plain text, did you?
        $password = hash("sha256", $password);

        if ($this->userExists($email)) {
            echo json_encode($this->EXISTS_STATUS);
            return;
        }

        /*
        Just a note: while a collision is theoretically possible in the sha256, the chance is so small that its considered impossible.
        So, no, dont need to worry about that.

        1.1579209 × 10^77 -> bigger than the number of atoms in the observable universe
        */

        $this->model->registerUser($email, $password, $name);

        // jwt
        $user = $this->model->loginUser($email, $password);
        $userJWT = $this->encodeJWT($user);

        // need to create a cookie here for the user. User logs in, I send token through cookie, user sends token again, user is verified
        //                                         hehehe travessuras (1 ano)                                  no https
        setcookie(Config::$jwtName, $userJWT, time() + (365 * 24 * 60 * 60), "/", "", Config::$isHttps, true);

        // approved 💯
        $status = $this->SUCCESS_STATUS;
        $status["redirect"] = Config::$route . "/chat";
        echo json_encode($status);
    }

    // invalidate user token
    public function logout () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        
        //           set the time to somewhere in the past to invalidate the token
        setcookie(Config::$jwtName, $jwt, time() - 3600, "/", "", Config::$isHttps, true);

        $status = $this->SUCCESS_STATUS;
        $status["redirect"] = Config::$route .  "/auth";
        echo json_encode($status);
    }

    // token is http-only, user cannot access it directly
    public function getToken () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!isset($jwt)) {
            echo $this->ERROR_STATUS;

            return;
        }

        $status = $this->SUCCESS_STATUS;
        $status["jwt"] = $jwt;
        echo json_encode($status);
    }

    private function userExists ($email) {
        $dbUser = $this->model->userExists($email);

        // if no user was found with those credentials
        if ($dbUser[0] === null) {
            return false;
        }

        return true;
    }
}
?>