<?php

use Ratchet\ConnectionInterface;

class UserHandler extends Handler {
    private array $connectedUsers; // holds connected users and their Object

    public function __construct () {
        parent::__construct();

        $this->connectedUsers = [];

        /* I would use the interface as a key if php allowed that, but it only accepts int, string, etc
        {
            1111: {
                User [Object],
                Interface [Object]
            },
            2222: {
                User [Object],
                Interface [Object]
            }
        }
        */
    }

    // false = couldnt save user. Will set the user as connected
    public function addUser (ConnectionInterface $interface, User $user) : bool {
        if (!isset($interface)) {
            return false;
        }
        if (!$this->isJwtValid($user->jwt)) {
            return false;
        }
        
        $resourceId = $interface->resourceId;

        $decodedJwt = $this->decodeJWT($user->jwt);
        $userDB = $this->database->fetchUserByHash($decodedJwt->user_hashed);
        if ($userDB === null) {
            return false;
        }

        $user->hash = $userDB["user_hashed"];
        $this->connectedUsers[$resourceId] = [
            "user" => $user,
            "interface" => $interface
        ];

        return true;
    }
    public function removeUser (int $resourceId) {
        $user = $this->connectedUsers[$resourceId];

        if ($user === null) {
            return;
        }

        $this->connectedUsers = $this->removeElement($this->connectedUsers, $resourceId);
    }

    // returns null or the ConnectionInterface
    public function getUserInterface (int $resourceId) : ConnectionInterface|null {
        if (!isset($resourceId)) {
            return null;
        }

        foreach ($this->connectedUsers as $key => $value) {
            if ($key === $resourceId) {
                return $value["interface"];
            }
        }

        return null;
    }

    // returns null or the resource id of the user. Returns -1 if the id was not found
    public function getUserResourceByHash (string $userHash) : int {
        foreach ($this->connectedUsers as $key => $value) {
            if ($value["user"]->hash === $userHash) {
                return $key;
            }
        }

        return -1;
    }

    // returns an User object or null
    public function getUserObject (int $resourceId) : User|null {
        foreach ($this->connectedUsers as $key => $value) {
            if ($key == $resourceId) {
                return $value["user"];
            }
        }

        return null;
    }

    // returns the user id
    public function getUserByConversation (string $conversationHash, string $ignoreUser) : string {
        $user = $this->database->fetchUserByConversation($conversationHash, $ignoreUser); 
        // handle null
        return $user;
    }

    public function getConnectedUsers () : array {
        return $this->connectedUsers;
    }
    public function getUserFriends (string $userHash) : array {
        if ($userHash === null) {
            return null;
        }

        $friends = $this->database->fetchUserFriends($userHash);
        return $friends;
    }

    // friends
    public function warnFriends (string $userHash, int $status) {
        $a = new Message();
        $a->type = "user-status";
        $a->content = "{$userHash};{$status}";

        $friends = $this->getUserFriends($userHash);
        foreach ($friends as $friend) {
            $friendResource = $this->getUserResourceByHash($friend["user_hashed"]);
            $friendInterface = $this->getUserInterface($friendResource);

            if ($friendInterface === null) {
                continue;
            }

            $friendInterface->send(Message::messageToJson($a));
        }
    }
    public function warnUser (string $userHash) {
        $userInterface = $this->getUserInterface($this->getUserResourceByHash($userHash));
        $onlineFriends = array();

        if ($userInterface === null) {
            return;
        }

        // get online friends
        $friends = $this->getUserFriends($userHash);
        foreach ($friends as $friend) {
            $friendResource = $this->getUserResourceByHash($friend["user_hashed"]);

            if ($friendResource === -1) {
                continue;
            }

            array_push($onlineFriends, $friend["user_hashed"]);
        }

        // warn user about the online users
        foreach ($onlineFriends as $friend) {
            $a = new Message();
            $a->type = "user-status";
            $a->content = "{$friend};1";

            $userInterface->send(Message::messageToJson($a));
        }
    }

    // chat gpt code. If it works it works
    function removeElement(array $array, $keyToRemove) : array {
        // Check if the key exists in the array
        if (array_key_exists($keyToRemove, $array)) {
            // Remove the element by its key
            unset($array[$keyToRemove]);
        }
        
        // Return the array without the specified key and value
        return $array;
    }
}
?>