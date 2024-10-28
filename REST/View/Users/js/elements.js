const ownerPfps = document.getElementsByClassName("owner-pfp");
const resultsDiv = document.getElementById("results-div");
const searchInput = document.getElementById("input");

const selectablesDiv = document.getElementById("selectables-div");

const chatSelectable = document.getElementById("chat-selectable");
const loginSelectable = document.getElementById("login-selectable");
const homeSelectable = document.getElementById("home-selectable");
const friendsSelectable = document.getElementById("friends-selectable");
const selectablesIcon = document.getElementById("selectables-icon");

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

selectablesIcon.addEventListener("click", () => {
    selectablesState = !selectablesState;

    if (selectablesState) {
        selectablesDiv.className = "selectables-open";
    }
    else {
        selectablesDiv.className = "selectables-close";
    }
});

searchInput.addEventListener("input", () => {
    let value = searchInput.value.trim();
    results = [];

    // Clear the previous timer
    clearTimeout(typingTimer);

    // search for the user after user stop typing
    typingTimer = setTimeout(() => {        
        if (value !== "") {
            anime({
                targets: resultsDiv,
                borderColor: ["#000000", "#ffffff"],
                opacity: [0, 1],
                duration: 750
            });

            searchUsers(value);
        }
        else if (value === "") {
            results.push(resultsDiv); // this is just so I can change the opacity of every result, as well as the opacity of the results div
            anime({
                targets: results,
                opacity: [1, 0],
                duration: 750,
                complete: (anim) => {
                    resultsDiv.innerHTML = "";
                }
            });
        }
    }, 800);
});

for (let i = 0; i < ownerPfps.length; i++) {
    ownerPfps[i].addEventListener("click", () => {
        window.location.href = "/TCC/Project/REST/owner";
    });
}
