<?php 
class User {
    public string $hash;

    public string $aesKey;
    public string $jwt;

    public function __construct () {
        $this->hash = "";
        $this->aesKey = "";
        $this->jwt = "";
    }

    // takes a raw json string
    public static function jsonToUser (string $jsonStr) : User {
        $json = json_decode($jsonStr, true);

        $jsonHash = $json["user_hashed"];
        $jsonAes = $json["aes"];
        $jsonJwt = $json["jwt"];

        $user = new User();
        $user->hash = $jsonHash ? $jsonHash !== null : "";
        $user->aesKey = $jsonAes ? $jsonAes !== null : "";
        $user->jwt = $jsonJwt ? $jsonJwt !== null : "";

        return $user;
    }
    public static function arrayToUser (array $array) : User {
        return User::jsonToUser(json_encode($array)); // hehe
    }
}
?>