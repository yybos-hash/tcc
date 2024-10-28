function toggleConversation () {
    if (!isMobileViewport) {
        return;
    }
    if (isBitchOpen && !isConversationOpen) {
        chatsContainer.style.display = chatsContainer.style.display == "none" ? "flex" : "none";
        conversationContainer.style.display = conversationContainer.style.display == "flex" ? "none" : "flex";

        return;
    }

    const top = "1.5rem";
    const bottom = !isBitchOpen ? "5.75rem" : "1.5rem";

    const right = "87.5%";
    const left = "87.5%";

    if (isConversationOpen) {
        isConversationOpen = false;
        currentChat = "";

        chatsContainer.style.display = "flex";
        conversationContainer.style.display = "none";

        anime({
            targets: "#menu-button",
            top: [bottom, top],
            left: [left, right],
            duration: "300",
            easing: "easeInOutQuad",
        });
    }
    else {
        isConversationOpen = true;
    
        chatsContainer.style.display = "none";
        conversationContainer.style.display = "flex";

        anime({
            targets: "#menu-button",
            top: [top, bottom],
            left: [right, left],
            duration: "300",
            easing: "easeInOutQuad",
        });
    }
}

let loadingAnim = anime({
    targets: "#foreground-icon-div",
    rotate: [0, 360],
    loop: true,
    duration: 1750
});
