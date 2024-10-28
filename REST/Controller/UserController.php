<?php 
require_once "Controller.php";

class UserController extends Controller {
    private $model;

    public function __construct () {
        parent::__construct();
        $this->model = new UserModel();
    }

    public function getOwnerView () {
        $jwt = $_COOKIE[Config::$jwtName];

        // if user already has a token, direct him to the chats page, else he goes to the login page
        if ($this->isJwtValid($jwt)) {
            include "View/Owner/index.html";
        }
        else {
            header("Location: " . Config::$route . "/auth");            
        }
    }
    public function getUserView () {
        $jwt = $_COOKIE[Config::$jwtName];

        // if user already has a token, direct him to the chats page, else he goes to the login page
        if ($this->isJwtValid($jwt)) {
            include "View/User/index.html";
        }
        else {
            header("Location: " . Config::$route . "/auth");            
        }
    }

    public function getUserInfo () {
        $jwt = $_COOKIE[Config::$jwtName];
        $userHash = filter_input(INPUT_GET, "u", FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        $decodedJwt = $this->decodeJWT($jwt);

        if (isset($userHash))
            $user = $this->model->getUserInfo($userHash);
        else
            $user = $this->model->getUser($decodedJwt->user_hashed);

        if ($user === null) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        $status = $this->SUCCESS_STATUS;
        $status["user"] = $user;
        echo json_encode($status);
    }

    public function updateUserInfo () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        $email = trim(filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL));

        if (!isset($email)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        // no no no no no no, the user cannot simply do an XSS attack :sob:
        $name = trim(filter_input(INPUT_POST, "name", FILTER_SANITIZE_SPECIAL_CHARS));
        $class = trim(filter_input(INPUT_POST, "class", FILTER_SANITIZE_NUMBER_INT));
        $course = trim(filter_input(INPUT_POST, "course", FILTER_SANITIZE_SPECIAL_CHARS));
        $desc = trim(filter_input(INPUT_POST, "desc", FILTER_SANITIZE_SPECIAL_CHARS));

        $update = $this->model->updateUserInfo($decodedJwt->user_hashed, $email, $name, $class, $course, $desc);

        if ($update === null) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        
        $status = $this->SUCCESS_STATUS;
        $status["update"] = $update;
        echo json_encode($status);
    }

    public function uploadUserPicture () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        // Check if the file parameter is set in the FormData
        if (isset($_FILES['pfp']) && $_FILES['pfp']['error'] === UPLOAD_ERR_OK) {
            $image64 = base64_encode(file_get_contents($_FILES['pfp']['tmp_name']));
        } else {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        // this will shrink the image to 256x256 and convert it to jpeg
        $imageData = $this->squishProfileImage($image64);

        $op = $this->model->setUserPicture($decodedJwt->user_hashed, $imageData);
        if ($op === false) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        $status = $this->SUCCESS_STATUS;
        $status["img"] = $imageData;
        echo json_encode($status);
    }
    public function getUserPicture () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        $userHash = filter_input(INPUT_GET, "u", FILTER_SANITIZE_SPECIAL_CHARS);
        if (!isset($userHash)) {
            $userHash = $decodedJwt->user_hashed;
        }

        $base64 = $this->model->getUserPicture($userHash);
        if ($base64 === null) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        $status = $this->SUCCESS_STATUS;
        $status["img"] = $base64;
        echo json_encode($status);
    }

    public function uploadUserBackground () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        // Check if the file parameter is set in the FormData
        if (isset($_FILES['background']) && $_FILES['background']['error'] === UPLOAD_ERR_OK) {
            $image64 = base64_encode(file_get_contents($_FILES['background']['tmp_name']));
        } else {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        // this will shrink the image to 256x256 and convert it to jpeg
        $imageData = $this->squishProfileBackground($image64);

        $op = $this->model->setUserBackground($decodedJwt->user_hashed, $imageData);
        if ($op === false) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        $status = $this->SUCCESS_STATUS;
        $status["img"] = $imageData;
        echo json_encode($status);
    }
    public function getUserBackground () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        $userHash = filter_input(INPUT_GET, "u", FILTER_SANITIZE_SPECIAL_CHARS);
        if (!isset($userHash)) {
            $userHash = $decodedJwt->user_hashed;
        }

        $base64 = $this->model->getUserBackground($userHash);
        if ($base64 === null) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        $status = $this->SUCCESS_STATUS;
        $status["img"] = $base64;
        echo json_encode($status);
    }

    public function searchUser () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $userName = trim(filter_input(INPUT_GET, "s", FILTER_SANITIZE_SPECIAL_CHARS));

        if ($userName === null) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        $users = $this->model->searchUser($userName);

        $status = $this->SUCCESS_STATUS;
        $status["users"] = $users;
        echo json_encode($status);
    }

    public function getFriends () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        $friends = $this->model->getFriends($decodedJwt->user_hashed);
        if ($friends === null) {
            echo json_encode($this->NOTFOUND_STATUS);
            return;
        }

        $status = $this->SUCCESS_STATUS;
        $status["friends"] = $friends;
        echo json_encode($status);
    }

    public function getPendingFriends () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        $pending = $this->model->getPendingFriends($decodedJwt->user_hashed);
        if ($pending === null) {
            echo json_encode($this->NOTFOUND_STATUS);
            return;
        }

        $status = $this->SUCCESS_STATUS;
        $status["friends"] = $pending;
        echo json_encode($status);
    }

    public function acceptFriendship () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        $friendshipId = filter_input(INPUT_POST, "id", FILTER_SANITIZE_SPECIAL_CHARS);
        if ($friendshipId === null) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        $success = $this->model->acceptFriendship($decodedJwt->user_hashed, $friendshipId);
        if (!$success) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }

        echo json_encode($this->SUCCESS_STATUS);
    }

    public function deleteAccount () {
        $jwt = $_COOKIE[Config::$jwtName];

        if (!$this->isJwtValid($jwt)) {
            echo json_encode($this->ERROR_STATUS);
            return;
        }
        $decodedJwt = $this->decodeJWT($jwt);

        $deleted = $this->model->deleteUser($decodedJwt->user_hashed);    
        $status = ($deleted ? $this->SUCCESS_STATUS : $this->ERROR_STATUS);
    
        if ($status == $this->SUCCESS_STATUS) {
            setcookie(Config::$jwtName, $jwt, time() - 3600, "/", "", Config::$isHttps, true); // invalidate token
            $status["redirect"] = Config::$route . "/auth";
        }
        echo json_encode($status);
    }

    // chat gpt made this.
    private function squishProfileImage ($base64) {
        $newWidth = 350;
        $newHeight = 350;
        
        $outputQuality = 70;
        
        $imageData = base64_decode($base64);
        $sourceImage = imagecreatefromstring($imageData);

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    
        // Preserve transparency for PNG and GIF images
        // Get the image type from the image resource
        $imageInfo = getimagesizefromstring($imageData);
        $imageType = $imageInfo[2];
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagecolortransparent($resizedImage, imagecolorallocatealpha($resizedImage, 0, 0, 0, 127));
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
        }
    
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
    
        // Output the resized image as base64
        ob_start();
        imagejpeg($resizedImage, null, $outputQuality);
        $resizedImageData = ob_get_contents();
        ob_end_clean();
    
        // Free up memory
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
    
        // Return base64-encoded image string
        return base64_encode($resizedImageData);
    }
    private function squishProfileBackground ($base64) {
        $newWidth = 2000;
        $newHeight = 750;
        
        $outputQuality = 80;
        
        $imageData = base64_decode($base64);
        $sourceImage = imagecreatefromstring($imageData);

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    
        // Preserve transparency for PNG and GIF images
        // Get the image type from the image resource
        $imageInfo = getimagesizefromstring($imageData);
        $imageType = $imageInfo[2];
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagecolortransparent($resizedImage, imagecolorallocatealpha($resizedImage, 0, 0, 0, 127));
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
        }
    
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
    
        // Output the resized image as base64
        ob_start();
        imagejpeg($resizedImage, null, $outputQuality);
        $resizedImageData = ob_get_contents();
        ob_end_clean();
    
        // Free up memory
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
    
        // Return base64-encoded image string
        return base64_encode($resizedImageData);
    }
}
?>