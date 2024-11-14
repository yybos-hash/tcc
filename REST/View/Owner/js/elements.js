const dashPfp = document.getElementById("dash-pfp");
const pfpInput = document.getElementById("pfp-input");
const dashName = document.getElementById("dash-name");

const nameInput = document.getElementById("name-input");
const emailInput = document.getElementById("email-input");
const classInput = document.getElementById("class-input");
const courseInput = document.getElementById("course-input");
const descriptionInput = document.getElementById("description-input");

const profileButton = document.getElementById("profile-button");
const settingsButton = document.getElementById("settings-button");
const friendsButton = document.getElementById("friends-button");
const logoutButton = document.getElementById("logout-button");

const profileDiv = document.getElementById("edit-div");
const settingsDiv = document.getElementById("settings-div");
const friendsDiv = document.getElementById("friends-div");

const passwordCheckbox = document.getElementById("password-checkbox");
const saveButton = document.getElementById("save-button");

const notificationsDiv = document.getElementById("notifications-div");
const notificationsDot = document.getElementById("notifications-dot");

const notificationWindow = document.getElementById("notification-window");
const notificationWindowContent = document.getElementById("notification-window-content");
const notificationWindowClose = document.getElementById("notification-window-close");

const selectablesDiv = document.getElementById("selectables-div");
const selectablesIcon = document.getElementById("selectables-icon");
const selectables = document.getElementById("selectables");

const friendsHolder = document.getElementById("friends-holder");

const popupDiv = document.getElementById("popup-div");
const popupMessageDiv = document.getElementById("popup-message-div");
const popupButtonsDiv = document.getElementById("popup-buttons-div");
const popupMessage = document.getElementById("popup-message");
const popupButton = document.getElementById("popup-message-button");

const chatSelectable = document.getElementById("chat-selectable");
const loginSelectable = document.getElementById("login-selectable");
const homeSelectable = document.getElementById("home-selectable");
const friendsSelectable = document.getElementById("friends-selectable");

const profileViewBackground = document.getElementById("profile-view-user-profile-background");
const profileViewPicture = document.getElementById("profile-view-user-profile-picture");

const backgroundInputDiv = document.getElementById("background-input-div");
const backgroundInput = document.getElementById("background-input");

const addFriendButton = document.getElementById("add-friend-button");

const deleteAccountButton = document.getElementById("delete-account-button");

chatSelectable.addEventListener("click", () => {
    window.location.href = "/TCC/Project/REST/chat";
});
loginSelectable.addEventListener("click", () => {
    window.location.href = "/TCC/Project/REST/auth";
});
homeSelectable.addEventListener("click", () => {
    window.location.href = "/TCC/Project/REST/home";
});
friendsSelectable.addEventListener("click", () => {
    window.location.href = "/TCC/Project/REST/users";
});

dashPfp.addEventListener("click", () => {
    openPage(profileButton);
});
profileViewPicture.addEventListener("click", () => {
    pfpInput.click();
});

backgroundInputDiv.addEventListener("click", () => {
    backgroundInput.click();
});

addFriendButton.addEventListener("click", () => {
    window.location.href = "/TCC/Project/REST/users";
});

saveButton.addEventListener("click", () => {
    // updateInfo(emailInput.value, nameInput.value, classInput.value, courseInput.value, descriptionInput.value);
    updateInfo(emailInput.value, nameInput.value, null, null, descriptionInput.value);
});
logoutButton.addEventListener("click", () => {
    logout();
});
notificationsDiv.addEventListener("click", () => {
    notificationsWindowState = !notificationsWindowState;
    
    if (notificationsWindowState) {
        openNotifications();
    }
    else {
        closeNotifications();
    }
});

profileButton.addEventListener("click", () => {
    openPage(profileButton);
});
settingsButton.addEventListener("click", () => {
    openPage(settingsButton);
});
friendsButton.addEventListener("click", () => {
    openPage(friendsButton);
});

notificationWindowClose.addEventListener("click", () => {
    notificationsWindowState = !notificationsWindowState;

    if (notificationsWindowState) {
        openNotifications();
    }
    else {
        closeNotifications();
    }
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

pfpInput.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file === null)
        return;

    uploadProfilePicture(file);
});
backgroundInput.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file === null)
        return;

    uploadProfileBackground(file);
});

deleteAccountButton.addEventListener("click", () => {
    showPopup("Você tem certeza?", {
        "Não": function () {},
        "Sim": function () {
            deleteAccount();
        }
    });
});

popupDiv.addEventListener("click", () => {
    closePopup();
});