<?php 
require "../vendor/autoload.php";
require_once "autoRequire.php";

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Server implements MessageComponentInterface {
    private MessageHandler $messageHandler;
    private UserHandler $userHandler;

    private array $keys;

    public function __construct () {
        $this->messageHandler = new MessageHandler();
        $this->userHandler = new UserHandler();

        $this->keys = [];
    }

    // client connected, yay
    public function onOpen (ConnectionInterface $conn) {
        // AES and Diffie-Hellman
        $privateKey = DiffieHellman::generatePrivateKey(256); // private key for deffie hellman

        // we do the handshake (Diffie-Hellman)
        $prime = DiffieHellman::generateRandomPrime();
        $generator = 5;
        
        $publicKey = bcpowmod($generator, $privateKey, $prime);
        
        // so, the server will have a public "key" for each user
        // listen, this is not the best way, but its the only way I could think of
        $this->keys[$conn->resourceId] = [
            "public" => $publicKey,
            "private" => $privateKey,
            "prime" => $prime
        ];

        // send the things to the user
        $conn->send(json_encode([
            "message_type" => "handshake",
            "p" => $prime,
            "g" => $generator,
            "public-key" => $publicKey
        ]));

        echo "conn {$conn->resourceId}\n";
    }

    public function onMessage (ConnectionInterface $from, $msg) {
        try {
            try {
                // get the sender through resourceId
                $sender = $this->userHandler->getUserObject($from->resourceId);

                // user not connected
                if (!isset($sender))
                    throw new TypeError(); 

                if ($sender->aesKey === null)
                    return;

                $js = json_decode($msg, true);
                $cipher = $js["encrypted"];
                $iv = $js["iv"];
                $authTag = $js["authTag"];

                // decrypt the message from the sender with his key
                $decrypted = AES::decrypt($cipher, $sender->aesKey, $iv, $authTag);

                $msg = json_decode($decrypted, true);
            }
            catch (TypeError $a) {
                // if the server couldnt decrypt the message its probably because its the login info (if its not the login the user sent something wrong)
                try {
                    $msg = json_decode($msg, true);

                    $user = new User();

                    $serverKeys = $this->keys[$from->resourceId];
                    $userPublicKey = $msg["public-key"];

                    if (!isset($serverKeys)) 
                        return;
                    
                    if ($userPublicKey === null)
                        return;    

                    if ($msg["jwt"] === null)
                        return;

                    // secret key calculated from the diffie-hellman (derived)
                    $secretKey = bcpowmod($userPublicKey, $serverKeys["private"], $serverKeys["prime"]);

                    // hash secret key
                    $user->aesKey = hash("sha256", $secretKey);

                    // save the user's jwt
                    $user->jwt = $msg["jwt"];

                    // this method will see if the user exists in the db and save him if he does
                    $result = $this->userHandler->addUser($from, $user);
                    if (!$result) {
                        $error = new Message();
                        $error->type = "status";
                        $error->content = "-1";

                        $from->send(Message::messageToJson($error));
                    }
                    else {
                        $success = new Message();
                        $success->type = "status";
                        $success->content = "0";
                    
                        $from->send(Message::messageToJson($success));

                        // warn his friends that he is online
                        $userHash = $this->userHandler->getUserObject($from->resourceId)->hash;
                        $this->userHandler->warnFriends($userHash, 1);

                        // warn user about his online friends
                        $this->userHandler->warnUser($userHash);
                    }

                    // remove these keys from the array, we dont need them anymore
                    $this->keys = $this->removeResource($this->keys, $from->resourceId);
                    return;
                }
                catch (Exception $f) {
                    echo "{$f}\n";
                    // user must be retarded cause this shouldnt happen
                    return;
                }
            }
            catch (Exception $e) {
                echo "{$e}\n";
                return; // if any other exception occurs
            }

            if ($msg === null) {
                echo "Oh no, the msg is null!\n";
                return;
            }
            // when sending the message-status the content is not sent. If message_content exists and it's empty
            if (isset($msg["message_content"]) && empty($msg["message_content"])) {
                return;
            }

            switch ($msg["message_type"]) {
                case "message":
                    $message = Message::jsonToMessage(json_encode($msg));

                    echo "{$from->resourceId}: {$message->content}\n";

                    // check to see if nothing is null
                    if ($message->conversationHash === null) {
                        echo "Conversation was null ({$message->conversationHash})\n";
                        return;
                    }
                    
                    // get sender object
                    $sender = $this->userHandler->getUserObject($from->resourceId);

                    // gets the user in the conversation (it will ignore the user who sent the message)
                    $receiverHash = $this->userHandler->getUserByConversation($message->conversationHash, $sender->hash);
                    $receiverResource = $this->userHandler->getUserResourceByHash($receiverHash); // the resourceId

                    $message->senderHash = $sender->hash;

                    // user resource not found (not connected)
                    if ($receiverResource === -1) {
                        // send message to database
                        $postedMessage = $this->messageHandler->postMessage($message, false); // false means the message wasnt delivered, only sent
                        if ($postedMessage === null) {
                            return; // nonononononono this shouldnt happen
                        }

                        // send the message back to the user who sent it (but now it has the hash and state)
                        $from->send(json_encode(AES::encrypt(Message::messageToJson($postedMessage), $sender->aesKey)));
                        
                        echo "Receiver seems to be offline.\n";
                        return;
                    }

                    // send message to database
                    $postedMessage = $this->messageHandler->postMessage($message);
                    if ($postedMessage === null) {
                        return; // nonononononono this shouldnt happen
                    }

                    // get the receiver clientInterface
                    $receiverConn = $this->userHandler->getUserInterface($receiverResource);
                    $receiver = $this->userHandler->getUserObject($receiverResource);

                    // send the reencrypted message to the receiver
                    $receiverConn->send(json_encode(AES::encrypt(Message::messageToJson($postedMessage), $receiver->aesKey)));

                    // send the message back to the user who sent it (but now it has the hash and status)
                    $from->send(json_encode(AES::encrypt(Message::messageToJson($postedMessage), $sender->aesKey)));
                    break;
                case "message-status":
                    $message = Message::jsonToMessage(json_encode($msg));
                    $status = $message->status;

                    // for now ill leave this here
                    if ($status !== "seen") {
                        break;
                    }

                    if ($message->conversationHash == null) {
                        echo "Conversation Hash was null on message-status\n";
                        break;
                    }
                    if ($message->senderHash == null) {
                        echo "Sender Hash was null on message-status\n";
                        break;
                    }

                    $this->messageHandler->setMessageStatus($message, $status);

                    /*
                        gets the user who sent the message (this is actually a "reference" to the original message)
                        for example: user1 sent "hello", user2 sent a status of "seen" for this message, the status's senderHash will be user1's hash
                        had to clarify this since I also got confused
                    */
                    $senderResource = $this->userHandler->getUserResourceByHash($message->senderHash); // the resourceId

                    // user resource not found (not connected or does not exist)
                    if ($senderResource === -1) {
                        break;
                    }
                    
                    // get the sender clientInterface
                    $senderConn = $this->userHandler->getUserInterface($senderResource);
                    $sender = $this->userHandler->getUserObject($senderResource);

                    // send the reencrypted message to the receiver
                    $senderConn->send(json_encode(AES::encrypt(Message::messageToJson($message), $sender->aesKey)));
                    break;
                default:
                    echo "I don't think this type exists ({$msg['message_type']})\n";
                    break;
            }            
        }
        catch (Exception $e) {
            echo "Exception in onMessage: {$e}\n";
        }
    }

    public function onClose (ConnectionInterface $conn) {
        // warn his friends that he is offline
        $user = $this->userHandler->getUserObject($conn->resourceId);
        if ($user !== null)
            $this->userHandler->warnFriends($user->hash, 0);
        
        $this->userHandler->removeUser($conn->resourceId);
        echo "{$conn->resourceId} disconnected\n";
    }

    public function onError (ConnectionInterface $conn, \Exception $e) {
        // Error occurred
        $this->userHandler->removeUser($conn->resourceId);
        echo "Error occurred on {$conn->resourceId}: {$e->getMessage()}\n";

        $conn->close();
    }

    // chat gpt code. If it works it works. Edit: it does not work Edit2: it works now
    function removeResource(array $array, $keyToRemove) : array {
        // Check if the key exists in the array
        if (array_key_exists($keyToRemove, $array)) {
            // Remove the element by its key
            unset($array[$keyToRemove]);
        }
        
        // Return the array without the specified key and value
        return $array;
    }
}

header("Access-Control-Allow-Origin: *"); // Allow all origins
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$server_host = "127.0.0.1";
$server_port = "5135";

/*
$loop = React\EventLoop\Loop::get();

// Updated to use SocketServer
$webSock = new React\Socket\SocketServer("{$server_host}:{$server_port}", [], $loop);

// SSL context
$secureWebSock = new React\Socket\SecureServer($webSock, $loop, [
    'local_cert' => __DIR__ . '/cert/certificate.crt',
    'local_pk' => __DIR__ . '/cert/private.key',
    'allow_self_signed' => true, // Set to false for production
    'verify_peer' => false, // Set to true for production
]);

// Use httpServer and WsServer for browser support
$server = new IoServer(
    new HttpServer(
        new WsServer(
            new Server()
        )
    ),
    $secureWebSock,
    $loop
);

echo "Server started on {$server_host}:{$server_port}\n";
$server->run();
*/


// Use httpServer and WsServer for browser support
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Server()
        )
    ),
    intval($server_port),
    $server_host
);

echo "Server started on {$server_host}:{$server_port}\n";
$server->run();

?>