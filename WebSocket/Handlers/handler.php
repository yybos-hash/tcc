<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Handler {
    protected $database;
    private $secretKey;
    private $algorithm;

    protected $ERROR_STATUS;
    protected $SUCCESS_STATUS;
    protected $NOTFOUND_STATUS;
    protected $EXISTS_STATUS;
    protected $NOT_ALLOWED_STATUS;

    public function __construct () {
        $this->secretKey = "7a3f8b2e6d5c1a9f4e2b8c3d7a0f3b2e8d9c4e3b2a0f8e9d2c1a5f6e2b9d7c4"; // imagine all the people...
        $this->algorithm = "HS256"; // symmetric encryption. assymmetric is a pain in the ass
    
        $this->database = new Database();

        $this->ERROR_STATUS = ["status" => 0];
        $this->SUCCESS_STATUS = ["status" => 1];
        $this->NOTFOUND_STATUS = ["status" => -1];
        $this->EXISTS_STATUS = ["status" => -2];
        $this->NOT_ALLOWED_STATUS = ["status" => -3];
    }

    protected function decodeJWT (string $jwt) : stdClass {
        return JWT::decode($jwt, new Key($this->secretKey, $this->algorithm)); // used to decode the jwt
    }
    protected function encodeJWT (array $data) : string {
        return JWT::encode($data, $this->secretKey, $this->algorithm, null, null, ["kid" => null]); // set the key id (kid) as null
    }
    
    protected function isJwtValid (string $jwt) : bool {
        if (isset($jwt)) {
            $decodedJwt = $this->decodeJWT($jwt);
            if ($decodedJwt === null)
                return false;

            // see if the user really exists (he might have deleted his account)
            $user = $this->database->fetchUserByHash($decodedJwt->user_hashed);
            
            return $user !== null;
        }
        
        return false;
    }
}
?>