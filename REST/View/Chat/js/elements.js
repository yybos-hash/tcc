const input = document.getElementById("mf-io-input");
const buttonSend = document.getElementById("mf-io-send");

const chatsContainer = document.getElementById("chats-container");
const conversationContainer = document.getElementById("conversation-container");

const chatsOverview = document.getElementById("chats-overview");
const conversationHolder = document.getElementById("mf-conversation-holder");

const mfProfileImage = document.getElementById("mf-profile-holder-image");
const mfUsername = document.getElementById("mf-profile-info-username");
const mfClass = document.getElementById("mf-profile-info-class");
const mfCourse = document.getElementById("mf-profile-info-course");
const mfDesc = document.getElementById("mf-profile-info-desc");
const mfOnlineDiv = document.getElementById("mf-profile-status");

const foreground = document.getElementById("foreground");

const overviewProfileImage = document.getElementById("overview-profile-holder-image");
const overviewProfileName = document.getElementById("profile-overview-name");
const overviewProfileDesc = document.getElementById("profile-overview-desc");

const selectablesDiv = document.getElementById("selectables-div");

const chatSelectable = document.getElementById("chat-selectable");
const loginSelectable = document.getElementById("login-selectable");
const homeSelectable = document.getElementById("home-selectable");
const friendsSelectable = document.getElementById("friends-selectable");
const selectablesIcon = document.getElementById("selectables-icon");

const menuButton = document.getElementById("menu-button");

const foregroundLoadingText = document.getElementById("foreground-loading-text");

const ownerPfps = document.getElementsByClassName("owner-pfp");
for (let i = 0; i < ownerPfps.length; i++) {
    ownerPfps[i].addEventListener("click", () => {
        window.location.href = "/TCC/Project/REST/owner";   
    });
}

loginSelectable.addEventListener("click", () => {
    window.location.href = "/TCC/Project/REST/auth";
});
homeSelectable.addEventListener("click", () => {
    window.location.href = "/TCC/Project/REST/home";
});
friendsSelectable.addEventListener("click", () => {
    window.location.href = "/TCC/Project/REST/users";
});

buttonSend.addEventListener("click", () => {
    sendMessage(input.value);
    input.value = "";
});
input.addEventListener("keyup", (event) => {
    if (event.key === "Enter") {
        sendMessage(input.value);
        input.value = "";
    }
});

overviewProfileImage.addEventListener("click", () => {
    foreground.style.display = "flex";
    foregroundProfileImage.src = overviewProfileImage.src;
});

menuButton.addEventListener("click", () => {
    toggleConversation();
});

selectablesIcon.addEventListener("click", () => {
    selectablesState = !selectablesState;

    if (selectablesState) {
        selectablesDiv.className = "selectables-open";
    }
    else {
        selectablesDiv.className = "selectables-close";
    }
});
