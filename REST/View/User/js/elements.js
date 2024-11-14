const userPfps = document.getElementsByClassName("user-profile-picture");
const userBackground = document.getElementById("user-profile-background");
const username = document.getElementById("user-profile-name");
const userClass = document.getElementById("user-profile-class");
const userCourse = document.getElementById("user-profile-course");
const userJoin = document.getElementById("user-profile-join");
const userDesc = document.getElementById("user-profile-desc");

const editProfileButton = document.getElementById("edit-profile-button");

editProfileButton.addEventListener("click", () => {
    window.location.href = "owner";
});