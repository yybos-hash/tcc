async function getUser (hash) {
    let url = "/TCC/Project/REST/user/get-user";

    if (hash !== null) {
        url += ("?u=" + hash);
    }

    await fetch(url)
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        console.log(status);

        if (status === 0) {
            console.log("fuck");
        }
        else if (status === 1) {
            let user = data.user;
            owner = user;

            updateInputs(user);
            updateProfilePicture();

            profileViewBackground.src = "data:image/jpeg;base64," + user.background;
        }
    })
    .catch(error => console.log(error.message));
}
async function updateInfo (email, name, userClass, course, description) {
    await fetch("/TCC/Project/REST/user/update-user", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            email: email,
            name: name,
            class: userClass,
            course: course,
            desc: description
        })
    })
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        console.log(status);

        if (status === 0) {
            console.log("fuck");
        }
        else if (status === 1) {
            let update = data.update;

            updateInputs(update);
            showPopup("Atualizado!", {
                "Ok": function () {}
            });
        }
    })
    .catch(error => console.log(error.message));
}
async function logout () {
    await fetch("/TCC/Project/REST/auth/logout", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        }
    })
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        if (status === 1) {
            window.location.href = data.redirect;
        }
    });
}

async function getPendingFriends () {
    await fetch("/TCC/Project/REST/user/get-pending-friends")
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        if (status === 0) {
            console.log("fuck");
        }
        else if (status === 1) {
            let friends = data.friends;
            notifications = friends;

            notificationsDot.style.display = notifications.length > 0 ? "block" : "none";
        }
    })
    .catch(error => console.log(error.message));
}
async function getFriends () {
    await fetch("/TCC/Project/REST/user/get-friends")
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        if (status === 0) {
            console.log("fuck");
        }
        else if (status === 1) {
            let friends = data.friends;
            let addLine = true;

            // remove all the users in case there is any
            friendsHolder.innerHTML = "";

            for (let i = 0; i < friends.length; i++) {
                // if its the last friend dont add the bottom line
                if (i === friends.length - 1) {
                    addLine = false;
                }

                addFriend(friends[i], addLine);
            }
        }
    })
    .catch(error => console.log(error.message));
}
async function acceptFriendship (friendshipHash) {
    await fetch("/TCC/Project/REST/user/accept-friendship", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            id: friendshipHash
        })
    })
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        console.log(status);

        if (status === 0) {
            console.log("fuck");
        }
        else {
            notificationsDot.style.display = "none";
            getFriends();
        }
    })
    .catch(error => console.log(error.message));
}

function addFriend (user, addLine) {
    const template = `
    <div class="friend-pfp">
        <img src="data:image/jpeg;base64,${user.pfp}" alt="">
    </div>

    <div class="friend-info">
        <div>
            <p class="friend-name">${user.user_name}</p>
            <p class="course">${user.user_description}</p>
        </div>
        <div>
            <p class="class">${user.user_class}</p>
            <p class="course">${user.user_course}</p>
        </div>
    </div>
    `;

    const friendDiv = document.createElement("div");
    friendDiv.innerHTML = template;
    friendDiv.className = "friend pointer";
    friendDiv.addEventListener("click", () => {
        window.location.href = "/TCC/Project/REST/user?u=" + user.user_hashed;
    });

    friendsHolder.appendChild(friendDiv);

    anime({
        targets: friendDiv,
        opacity: [0, 1],
        easing: "easeInOutQuad",
        duration: 200
    });

    if (!addLine) {
        return;        
    }

    const bottomLine = document.createElement("div");
    bottomLine.className = "line";

    friendsHolder.appendChild(bottomLine);

    anime({
        targets: bottomLine,
        opacity: [0, 1],
        easing: "easeInOutQuad",
        duration: 200
    });
}

function openNotifications () {
    notificationWindowContent.innerHTML = "";
    anime({
        targets: notificationWindow,
        opacity: [0, 1],
        easing: "easeInOutQuad",
        duration: 150,
        begin: function () {
            notificationWindow.style.display = "flex";
        }
    });

    for (let i = 0; i < notifications.length; i++) {
        const friendship = notifications[i];

        const template = `
        <img src="data:image/jpeg;base64,${friendship.pfp}" alt="">
        <div>
            <p>${friendship.user_name}</p>
            <p style="color: grey; font-size: 11pt">${friendship.user_class} ${friendship.user_course}</p>
        </div>
        `;

        const optionDiv = document.createElement("div");
        optionDiv.className = "notification-option";

        const buttonAccept = document.createElement("button");
        buttonAccept.innerHTML = "Aceitar";
        buttonAccept.addEventListener("click", () => {
            acceptFriendship(friendship.friendship_hashed);
            closeNotifications();

            // remove this friendship from notifications
            notifications = notifications.filter(item => item !== friendship);
        });

        optionDiv.innerHTML = template;
        optionDiv.appendChild(buttonAccept);

        notificationWindowContent.appendChild(optionDiv);
    }
}
function closeNotifications () {
    anime({
        targets: notificationWindow,
        opacity: [1, 0],
        easing: "easeInOutQuad",
        duration: 150,
        complete: function () {
            notificationWindow.style.display = "none";
        }
    });

    notificationWindowContent.innerHTML = "";
    notificationsWindowState = false;
}
function updateInputs (inputsObject) {
    let name = inputsObject.user_name.substr(0, 10);
    name = inputsObject.user_name.length > 10 ? name + "..." : name;

    const names = document.getElementsByClassName("owner-name");
    for (let i = 0; i < names.length; i++) {
        names[i].innerText = name;
    }

    const classes = document.getElementsByClassName("owner-class");
    for (let i = 0; i < classes.length; i++) {
        classes[i].innerText = inputsObject.user_class;
    }

    const courses = document.getElementsByClassName("owner-course");
    for (let i = 0; i < courses.length; i++) {
        courses[i].innerText = inputsObject.user_course;
    }

    const joins = document.getElementsByClassName("owner-join");
    for (let i = 0; i < joins.length; i++) {
        joins[i].innerText = inputsObject.user_join;
    }

    const descs = document.getElementsByClassName("owner-desc");
    for (let i = 0; i < descs.length; i++) {
        descs[i].innerText = inputsObject.user_description;
    }

    nameInput.value = inputsObject.user_name;
    emailInput.value = inputsObject.user_email;
    // classInput.value = inputsObject.user_class;
    descriptionInput.value = inputsObject.user_description;

    if (inputsObject.user_course !== null) {
        // courseInput.value = inputsObject.user_course.toLowerCase();
    }
}
function openPage (button) {
    if (button === profileButton) {
        profileDiv.style.display = "flex";
        friendsDiv.style.display = "none";
        settingsDiv.style.display = "none";
    }
    else if (button === friendsButton) {
        profileDiv.style.display = "none";
        friendsDiv.style.display = "block";
        settingsDiv.style.display = "none";
    }
    else if (button === settingsButton) {
        profileDiv.style.display = "none";
        friendsDiv.style.display = "none";
        settingsDiv.style.display = "flex";
    }
}

async function uploadProfilePicture (file) {
    const formData = new FormData();
    formData.append("pfp", file);
    
    await fetch("/TCC/Project/REST/user/upload-pfp", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 0)
            return;

        // update the owner pfp
        owner.pfp = data.img;

        // update the ui pfp
        updateProfilePicture();
    })
    .catch(error => console.log(error));
}
function updateProfilePicture () {
    const images = document.getElementsByClassName("owner-pfp");

    for (let i = 0; i < images.length; i++) {
        images[i].src = "data:image/jpeg;base64," + owner.pfp;
    }
}

async function uploadProfileBackground (file) {
    const formData = new FormData();
    formData.append("background", file);
    
    await fetch("/TCC/Project/REST/user/upload-background", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 0)
            return;

        owner.background = data.img;
        updateProfileBackground();
    })
    .catch(error => console.log(error));
}
function updateProfileBackground () {
    const images = document.getElementsByClassName("owner-background");

    for (let i = 0; i < images.length; i++) {
        images[i].src = "data:image/jpeg;base64," + owner.background;
    }
}

async function deleteAccount () {
    await fetch("/TCC/Project/REST/user/delete-user", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({})
    })
    .then(response => response.json())
    .then(data => {
        var status = data.status;
        console.log(status);

        if (status == 1) {
            window.location.href = data.redirect;
        }
    })
    .catch(error => console.log(error.message));
}

function showPopup (message, buttons) {
    // buttons must be an object with string values and function keys
    popupMessage.innerHTML = message;

    Object.entries(buttons).forEach(([k, v]) => {
        let button = document.createElement("button");
        button.innerHTML = k;
        button.addEventListener("click", v);

        popupButtonsDiv.appendChild(button);
    });
    
    anime({
        targets: popupDiv,
        opacity: [0, 1],
        begin: function () {
            popupDiv.style.display = "flex";
        }
    });
}
function closePopup () {
    // clean buttons
    popupButtonsDiv.innerHTML = "";
    
    anime({
        targets: popupDiv,
        opacity: [1, 0],
        complete: function () {
            popupDiv.style.display = "none";
        }
    });
}