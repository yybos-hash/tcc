function beginWebsocket () {
    if (jwt === null || jwt === undefined)
        return;

    con = new WebSocket('ws://localhost:5135');

    // when it connects to the server
    con.onopen = (e) => onOpen(e);

    // when it receives a message
    con.onmessage = (e) => onMessage(e);

    // when something goes wrong
    con.onerror = (e) => onError(e);

    // when the connection closes
    con.onclose = (e) => onClose(e);
}

function onOpen (e) {
    console.log("Connected");
    foregroundLoadingText.innerText = "Esperando Handshake do servidor...";
}
async function onMessage (e) {
    const data = e.data;
    let message = JSON.parse(data);

    let cipher = message.encrypted;
    let iv = message.iv;
    let authTag = message.authTag;

    // if its encrypted
    if (cipher !== undefined && iv !== undefined && authTag !== undefined) {
        try {
            let decrypted = await decryptMessage(cipher, aesKey, iv, authTag);
            
            if (decrypted == "") {
                console.log("decrypted bad");
    
                foregroundLoadingText.innerText = "Erro ao encriptar a conexão\nTentando de novo.";
                con.close();
                return;
            }
        
            message = JSON.parse(decrypted);
        }
        catch (exception) {
            console.log(exception);
        }
    }

    switch (message.message_type) {
        case "handshake":
            var prime = BigInt(message.p);
            var generator = BigInt(message.g);

            // generates private key
            var privateKey = randomPrivateKey(256);
            var publicKey = bcpowmod(generator, privateKey, prime); // send to the server 

            var serverPublicKey = BigInt(message["public-key"]);
            var secretKey = bcpowmod(serverPublicKey, privateKey, prime);

            // hash the secret key (since its only the secret key the second argument should be empty)
            hash(secretKey)
            .then((hashed) => {
                aesKey = hashed;
            });

            var loginMessage = {
                "type": "login",
                "public-key": publicKey.toString(),
                "jwt": jwt,
            };

            con.send(JSON.stringify(loginMessage));

            break;
        case "message":
            // update the last message
            const lastSaid = (message.fk_message_sender === owner.user_hashed ? "Você: " : "") + message.message_content;            
            document.getElementById("lastsaid-" + message.fk_message_conversation).innerHTML = (lastSaid.length > maxLastSaidLength ? lastSaid.substr(0, maxLastSaidLength) + "..." : lastSaid);    

            if (message.fk_message_sender === owner.user_hashed) {
                // well, since the server is sending back our message it means that the server received it (mark it as sent)
                // remove the unsent message (now sent) from the unsetMessages array
                unsentMessages = unsentMessages.filter(msg => msg.message_timestamp !== message.message_timestamp);                        

                // we also have the chats object. Need to change the status of the message from the chat that this message belongs to
                for (let i = 0; i < chats[message.fk_message_conversation].length; i++) {
                    const chatMessage = chats[message.fk_message_conversation][i];
                    
                    if (chatMessage.message_timestamp == message.message_timestamp) {
                        chats[message.fk_message_conversation]["messages"][i] = message;
                        break;
                    }
                }
            }
            else {
                chats[message.fk_message_conversation]["messages"].push(message);
            }

            // add message to the chat (if its open that is), and if the sender is not the user
            if (currentChat === message.fk_message_conversation) {  
                if (message.fk_message_sender !== owner.user_hashed) {
                    addMessage(message);
                    sendMessageStatus(message, "seen");
                }
                else {
                    // change style
                    const messageContainer = document.getElementById("message-container-" + message.message_timestamp);
                    if (messageContainer !== null) {
                        messageContainer.classList.remove("message-unsent");
                        messageContainer.classList.add("message-sent");                        
                    }
                }
            }

            break;
        case "status":
            if (message.message_content == 0) {
                document.dispatchEvent(dataLoadedEvent);
            }
            else {
                foregroundLoadingText.innerText = "Erro ao se conectar ao Websocket\nTentando de novo.";
                con.close();
            }

            break;
        case "user-status":
            let userHash = message.message_content.split(";")[0];
            let userStatus = message.message_content.split(";")[1];

            // look for the user and change his status. My fault, I made this so that every user is associated with a chat, and not independent
            let chatsKeys = Object.keys(chats);
            for (let i = 0; i < chatsKeys.length; i++) {
                if (chats[chatsKeys[i]]["user"]["user_hashed"] == userHash) {
                    chats[chatsKeys[i]]["user"]["user_status"] = (userStatus != 0); // this looks so stupid, I swear to god
                    break;
                }
            }

            let userOnlineDiv = document.getElementById("status-" + userHash);
            if (userOnlineDiv === null) {
                console.log("status-" + userHash + " does not exist. (user-status)");
                break;
            }

            anime({
                targets: [userOnlineDiv, mfOnlineDiv],
                opacity: [(userStatus == 0 ? 1 : 0), (userStatus == 0 ? 0 : 1)],
                duration: 800
            });
            break;
        case "message-status":
            // we also have the chats object. Need to change the status of the message from the chat that this message belongs to
            for (let i = 0; i < chats[message.fk_message_conversation]["messages"].length; i++) {
                const chatMessage = chats[message.fk_message_conversation]["messages"][i];
                
                if (chatMessage.message_timestamp == message.message_timestamp) {
                    chatMessage.message_status = message.message_status;
                    console.log("Updated message status. (message-status)");
                    break;
                }
            }

            // change style
            const messageContainer = document.getElementById("message-container-" + message.message_timestamp);
            if (messageContainer == null) {
                console.log("message-container-" + message.message_timestamp + " does not exist. (message-status)");
                break;
            }

            messageContainer.classList.remove("message-sent");
            messageContainer.classList.add("message-seen");
            break;
    }
}
function onClose (e) {
    beginWebsocket();
}
function onError (e) {
    console.log(e);

    foregroundLoadingText.innerText = "Erro ao se conectar com o Websocket\nTentando de novo.";
}