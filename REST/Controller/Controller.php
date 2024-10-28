<?php 
require_once "Model/Model.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Controller {
    private $model;

    private string $secretKey;
    private string $algorithm;

    protected array $ERROR_STATUS; // 0
    protected array $SUCCESS_STATUS; // 1
    protected array $NOTFOUND_STATUS; // -1
    protected array $EXISTS_STATUS; // -2
    protected array $NOT_ALLOWED_STATUS; // -3
    protected array $INVALID_EMAIL_STATUS;

    public function __construct () {
        $this->model = new Model();

        $this->secretKey = Config::$jwtKey; // imagine all the people...
        $this->algorithm = Config::$jwtEncryption;
    
        $this->ERROR_STATUS = ["status" => 0];
        $this->SUCCESS_STATUS = ["status" => 1];
        $this->NOTFOUND_STATUS = ["status" => -1];
        $this->EXISTS_STATUS = ["status" => -2];
        $this->NOT_ALLOWED_STATUS = ["status" => -3];
        $this->INVALID_EMAIL_STATUS = ["status" => -4];
    }

    protected function decodeJWT (string $jwt) : stdClass|null {
        try {
            return JWT::decode($jwt, new Key($this->secretKey, $this->algorithm)); // used to decode the jwt
        }
        catch (Exception $e) {
            return null;
        }
    }
    protected function encodeJWT (array $data) : string {
        return JWT::encode($data, $this->secretKey, $this->algorithm, null, null, ["kid" => null]); // set the key id (kid) as null
    }

    protected function isJwtValid ($jwt) : bool {
        if (isset($jwt)) {
            $decodedJwt = $this->decodeJWT($jwt);
            if ($decodedJwt === null)
                return false;

            // see if the user really exists (he might have deleted his account)
            $user = $this->model->fetchUser($decodedJwt->user_hashed);
            
            return $user !== null;
        }
        
        return false;
    }
}
?>