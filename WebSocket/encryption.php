<?php

class DiffieHellman {
    // generate big big prime number
    public static function generateRandomPrime () {
        $bitLength = 2048;

        $min = bcpow(2, $bitLength - 1);
        $max = bcsub(bcpow(2, $bitLength), "1");

        // generate numbers until a prime is found
        do {
            $candidate = gmp_strval(gmp_random_range($min, $max));
        } while (!gmp_prob_prime($candidate));

        return $candidate;
    }

    // generate private key (chatgpt)
    public static function generatePrivateKey($bitLength) {
        // Ensure that the bit length is valid
        if ($bitLength <= 0) {
            throw new Exception("Invalid bit length: it must be greater than zero");
        }
    
        $min = gmp_strval(gmp_pow(2, $bitLength - 1));
        $max = gmp_strval(gmp_sub(gmp_pow(2, $bitLength), 1));

        // Generate a random integer within the specified range
        return gmp_intval(gmp_random_range(gmp_init($min), gmp_init($max)));
    }
}

class AES {
    // Function to encrypt a message using AES-GCM
    public static function encrypt (string $message, string $key) : array {
        $iv = openssl_random_pseudo_bytes(12);
        $encrypted = openssl_encrypt($message, "aes-256-gcm", hex2bin($key), OPENSSL_RAW_DATA, $iv, $tag); // right here

        $data = [
            "encrypted" => bin2hex($encrypted),
            "iv" => bin2hex($iv),
            "authTag" => bin2hex($tag) // tag is declared in the function call
        ];

        return $data; 
    }

    // Function to decrypt a message using AES-GCM
    public static function decrypt (string $encryptedData, string $key, string $iv, string $authTag) : string {
        // Decrypt the ciphertext
        return openssl_decrypt(hex2bin($encryptedData), "aes-256-gcm", hex2bin($key), OPENSSL_RAW_DATA, hex2bin($iv), hex2bin($authTag));
    }
}
?>