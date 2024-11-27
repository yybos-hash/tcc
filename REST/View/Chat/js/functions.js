// using an argument cause I might call this function later in a different situation
async function sendMessage (str) {
    if (con === undefined || con.readyState != WebSocket.OPEN)
        return;

    if (owner === null || owner === undefined)
        return;

    if (aesKey === null || aesKey === undefined)
        return;
    
    if (currentChat === null || currentChat === undefined)
        return;

    str = sanitizeString(str).trim()
    if (str === "") {
        return;
    }

    var template = {
        "message_content": str,
        "message_timestamp": Date.now(), // returns the date in millisseconds
        "fk_message_conversation": currentChat,
        "message_type": "message",
        "message_status": "unsent"
    };

    // lets consider using HMAC (Only the future can tell us if I will use it) Update: I did not
    let encrypted = await encryptMessage(JSON.stringify(template), aesKey);

    con.send(JSON.stringify(encrypted));

    chats[currentChat]["messages"].push(template);
    unsentMessages.push(template);

    template["fk_message_sender"] = owner.user_hashed; // addMessage needs the message_sender to see if its the owner sending (who designed this shit)
    addMessage(template);
}
async function sendMessageStatus (message, status) {
    if (con === undefined || con.readyState != WebSocket.OPEN)
        return;

    if (owner === null || owner === undefined)
        return;

    if (aesKey === null || aesKey === undefined)
        return;

    // just to make sure I dont do anything dumb
    if (message.fk_message_sender === owner.user_hashed)
        return;

    var template = {
        "message_hashed": message.message_hashed,
        "fk_message_conversation": message.fk_message_conversation,
        "fk_message_sender": message.fk_message_sender,
        "message_timestamp": message.message_timestamp,
        "message_type": "message-status",
        "message_status": status
    };

    // lets consider using HMAC (Only the future can tell us if I will use it)
    let encrypted = await encryptMessage(JSON.stringify(template), aesKey);

    con.send(JSON.stringify(encrypted));
}

function addMessage (message) {
    let messageContainer = document.createElement("div");
    messageContainer.className = "message-container";

    let senderHash = message.fk_message_sender;
    const isOwner = senderHash === owner.user_hashed;
    const status = isOwner ? message.message_status : "seen";

    // if the same user sent two different messages then it will not show his picture (somewhat like discord)
    let img = "";
    let lastMessage = chats[message.fk_message_conversation]["messages"][chats[message.fk_message_conversation]["messages"].indexOf(message) - 1]; // confia
    if (lastMessage !== undefined && lastMessage.fk_message_sender == senderHash) {
        messageContainer.style.marginTop = "0";
    }
    if (lastMessage === undefined || lastMessage.fk_message_sender != senderHash) {
        img = `<img onclick="goToUserProfile('${senderHash}')" class="profile-image pointer" src="${"data:image/png;base64," + usersPicture[senderHash]}" alt="profile">`;
    }

    // if the owner is the sender
    if (isOwner) {
        messageContainer.style.flexDirection = "row-reverse";
    }
    
    const messageContainerStyle = `
        style="
            background-color: ${isOwner ? "rgba(16, 210, 0, 0.4);" : "rgba(94, 94, 94, 0.4)"};
            border-color: ${isOwner ? "rgba(16, 210, 0, 0.4);" : "rgba(94, 94, 94, 0.4)"};
        "
    `;

    const template = 
    `
        <div class="profile-holder" style="width: 2rem; height: 2rem;">
            <span class="profile-holder-span">
                ${img}
            </span>
        </div>

        <div id="message-container-${message.message_timestamp}" class="message-container-content ${"message-" + status}" ${messageContainerStyle}>
            <p class="text-style-1" style="font-size: 13pt; margin-bottom: 0.25rem;">${sanitizeString(message.message_content)}</p>
            <p class="text-style-1" style="font-size: 8pt; color: rgb(140, 140, 140);">${formatDate(message.message_timestamp)}</p>
        </div>
    `;

    messageContainer.innerHTML = template;
    conversationHolder.appendChild(messageContainer);

    conversationHolder.scrollTop = conversationHolder.scrollHeight;
}

async function openChat (chat) {
    if (isBitchOpen) {
        document.getElementById("bitch").style.display = "none";
        isBitchOpen = false;
    }

    currentChat = chat.conversation_hashed;

    let user_pfp = usersPicture[chats[currentChat]["user"]["user_hashed"]];

    // if we dont already have the image saved in the json
    if (user_pfp === null || user_pfp === undefined) {
        user_pfp = await getProfilePicture(chat.user_hashed);
    }

    let user_status = chats[currentChat]["user"]["user_status"];

    mfProfileImage.src = "data:image/jpeg;base64," + user_pfp;
    mfUsername.innerHTML = chat.user_name;
    mfClass.innerHTML = chat.user_class;
    mfCourse.innerHTML = chat.user_course;
    mfDesc.innerHTML = chat.user_description;
    mfOnlineDiv.style.display = (user_status ? "block" : "none");

    // gambiarra mencionada no others.js
    mfProfileImage.removeEventListener("click", mfProfileImageListener);
    
    let newListener = () => { goToUserProfile(chats[currentChat]["user"]["user_hashed"]); };
    mfProfileImageListener = newListener;
    mfProfileImage.addEventListener("click", newListener);

    if (isMobileViewport) {
        toggleConversation();
    }

    // bad, bad thing, not efficient. Updating the dom in a loop like this is foul
    const chatMessages = chats[currentChat]["messages"];
    for (let i = 0; i < chatMessages.length; i++) {
        const message = chatMessages[i];
        
        addMessage(message);

        if (message.message_status !== "seen") {
            // not awaiting this, cry about it
            sendMessageStatus(message, "seen");
        }
    }
}
async function addChat (chat) {
    var user_name = chat.user_name;
    var user_class = chat.user_class;
    var user_course = chat.user_course;

    if (user_name.length > maxUsernameLength) {
        user_name = user_name.substr(0, maxUsernameLength) + "...";
    }

    var user_pfp = usersPicture[chat.user_hashed];

    // if we dont already have the image saved in the json
    if (user_pfp === null || user_pfp === undefined) {
        user_pfp = await getProfilePicture(chat.user_hashed);
    }
    
    let lastSaid = "";
    let lastMessage = chats[chat.conversation_hashed]["messages"][chats[chat.conversation_hashed]["messages"].length - 1];
    if (lastMessage !== undefined) {
        lastSaid = (lastMessage.fk_message_sender === owner.user_hashed ? "Você: " : "") + lastMessage.message_content;
    }

    let chatDiv = document.createElement("div");
    chatDiv.className = "chat-object";
    chatDiv.addEventListener("click", () => {
        // if the chat is already open
        if (currentChat === chat.conversation_hashed) {
            // but if its in the mobile viewport then we toggle to the conversation div
            if (isMobileViewport) {
                toggleConversation();
            }

            return;
        }

        conversationHolder.innerHTML = ""; // clean all the messages
        openChat(chat);
    });

    const template = 
    `
        <div class="profile-holder">
            <span class="profile-holder-span">
                <img class="profile-image" src="${"data:image/jpeg;base64," + user_pfp}" alt="profile">
            </span>
            <div id="status-${chat.user_hashed}" class="profile-holder-online"></div>
        </div>

        <div class="chat-object-info">
            <div class="flex vertical">
                <p class="title-style-1">${user_name}</p>
                <p id="lastsaid-${chat.conversation_hashed}" class="title-style-1 class">${(lastSaid.length > maxLastSaidLength ? lastSaid.substr(0, maxLastSaidLength) + "..." : lastSaid)}</p>
            </div>

            <div class="flex vertical">
                <p class="text-style-1 class">${user_class}</p>
                <p class="text-style-1 course">${user_course}</p>
            </div>
        </div>
    `;

    chatDiv.innerHTML = template;
    chatsOverview.appendChild(chatDiv);
}

async function getChats () {
    return await fetch("/TCC/Project/REST/chat/get/chats")
    .then(response => response.json())
    .then((data) => {
        if (data.status === 0)
            return;

        return data.chats;
    })
    .catch(error => console.log(error.message));
}
async function getMessages (chatHash) {
    return await fetch("/TCC/Project/REST/chat/get/messages?chat=" + chatHash)
    .then(response => response.json())
    .then(data => {
        if (data.status === 0)
            return;

        return data.messages;
    })
    .catch(error => console.log(error));
}

async function getProfilePicture (userHash) {
    var args = "";

    // if there are no arguments then it will get our user's pfp (the user who sent the request)
    if (userHash !== null)
        args = "?u=" + userHash;

    return await fetch("/TCC/Project/REST/user/get/pfp" + args)
    .then(response => response.json())
    .then(data => {
        if (data.status === 0)
            return null;

        // automatically sets the owner profile picture (if the profile picture is the owner's)
        if (userHash === null) {
            owner.pfp = data.img;
            updateProfilePicture();
        }

        // save the base64 in the json (this is better than making a request everytime I need an user's pfp)
        usersPicture[userHash] = data.img;
        
        return data.img;
    })
    .catch(error => console.log(error));
}
function updateProfilePicture () {
    const images = document.getElementsByClassName("owner-pfp");

    for (let i = 0; i < images.length; i++) {
        images[i].src = "data:image/jpeg;base64," + owner.pfp;
    }
}

async function getUser (userHash) {
    var args = "";

    // if there are no arguments then it will get our user (the user who sent the request)
    if (userHash !== null)
        args = "?u=" + userHash;

    return await fetch("/TCC/Project/REST/user/get/user" + args)
    .then(response => response.json())
    .then(data => {
        if (data.status === 0)
            return;
        
        return data.user;     
    })
    .catch(error => console.log(error));
}

async function getToken () {
    return await fetch("/TCC/Project/REST/auth/token")
    .then(response => response.json())
    .then(data => {
        if (data.status === 0)
            return;

        return data.jwt;     
    })
    .catch(error => console.log(error));
}

function goToUserProfile (userHash) {
    window.location.href = "user?u=" + userHash;
}

// Function to convert a string to hexadecimal
function stringToHex(str) {
    let hex = '';
    for(let i = 0; i < str.length; i++) {
        // Get the hexadecimal representation of the character
        let charCode = str.charCodeAt(i).toString(16);
        // Add leading zero if necessary
        if (charCode.length < 2) {
            charCode = '0' + charCode;
        }
        hex += charCode;
    }
    return hex;
}
// Convert hexadecimal key to WordArray
function hexToWordArray(hex) {
    var words = [];
    for (var i = 0; i < hex.length; i += 2) {
        words.push(parseInt(hex.substr(i, 2), 16));
    }
    return CryptoJS.lib.WordArray.create(words);
}
function formatDate (timestamp) {
    let result;

    let date = new Date(parseInt(timestamp));

    let day = date.getDate() < 10 ? "0" + date.getDate() : date.getDate();
    let month = (date.getMonth() + 1 < 10) ? "0" + (date.getMonth() + 1) : (date.getMonth() + 1);
    let year = date.getFullYear();

    let minute = date.getMinutes() < 10 ? "0" + date.getMinutes() : date.getMinutes();
    let hour = date.getHours() < 10 ? "0" + date.getHours() : date.getHours();

    let currentDate = new Date(Date.now());

    if (currentDate.getDate() === date.getDate() && currentDate.getMonth() === date.getMonth() && currentDate.getFullYear() === year) {
        result = `${hour}:${minute}`;
    }
    else if (currentDate.getDate() === day) {
        result = `Ontem ${hour}:${minute}`;
    }
    else if (currentDate.getMonth() !== month && currentDate.getFullYear() === year) {
        result = `${day}/${month} ${hour}:${minute}`;
    }
    else {
        result = `${day}/${month}/${year} ${hour}:${minute}`;
    }

    return result;
}
function sanitizeString (str) {
    return str.replace(/<\/?[^>]+(>|$)/g, "");
}
