const loginEmail = document.getElementById("login-email");
const loginPassword = document.getElementById("login-password");

const buttonLogin = document.getElementById("button-login");

const registerEmail = document.getElementById("register-email");
const registerPassword = document.getElementById("register-password");
const registerName = document.getElementById("register-name");

const buttonRegister = document.getElementById("button-register");

const registerAccount = document.getElementsByClassName("register-account");
const title = document.getElementById("h1-title");

let logging = true;
let lastTimeout = null; // I absolutely hate javascript

let inputs = document.getElementsByTagName("input");
for (let i = 0; i < inputs.length; i++) {
    inputs[i].addEventListener("keyup", (event) => {
        if (event.key === "Enter") {
            (logging) ? login() : register();
            setMessages("");
        }
    });
}

function setMessages (str) {
    if (lastTimeout != null)
        clearTimeout(lastTimeout);

    const errorMessages = document.getElementsByClassName("error-message");

    for (let i = 0; i < errorMessages.length; i++) {
        errorMessages[i].innerText = str;
    }

    lastTimeout = setTimeout(() => {
        setMessages("");
    }, 4000);
}

async function login () {
    let email = loginEmail.value;
    let password = loginPassword.value;

    await fetch("/TCC/Project/REST/auth/login", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            email: email,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        if (status === -1) {
            setMessages("Usuário não encontrado.");
        }
        else if (status == 0) {
            setMessages("Senha incorreta.");
        }
        else if (status == -4) {
            setMessages("Email inválido.");
        }
        else if (status == 1) {
            window.location.href = data.redirect;
        }
    })
    .catch(error => console.log(error.message));
}

async function register () {
    let email = registerEmail.value;
    let password = registerPassword.value;
    let name = registerName.value;

    await fetch("/TCC/Project/REST/auth/register", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            email: email,
            password: password,
            name: name
        })
    })
    .then(response => response.json())
    .then(data => {
        let status = data.status;

        if (status == 0) {
            setMessages("Erro.");
        }
        else if (status == -2) {
            setMessages("Já existe um usuário com esse email.");
        }
        else if (status == -4) {
            setMessages("Email inválido.");
        }
        else if (status === 1) {
            window.location.href = data.redirect;
        }
    })
    .catch(error => console.log(error));
}

buttonLogin.addEventListener("click", () => {
    login();
    setMessages("");
});
buttonRegister.addEventListener("click", () => {
    register();
    setMessages("");
});


registerAccount[0].addEventListener("click", () => {
    logging = false;
    const animDuration = 250;
    const easing = "spring()";
    let offset = window.innerWidth < 640 ? 200 : 50;

    setMessages("");

    anime({
        targets: ["#login-div"],
        translateX: ["0svw", `-${offset}svw`],
        easing: easing,
        duration: animDuration / 2,
    });
    anime({
        targets: ["#register-div"],
        translateX: [`-${offset}svw`, "0svw"],
        easing: easing,
        duration: animDuration / 2
    });
});
registerAccount[1].addEventListener("click", () => {
    logging = true;
    const animDuration = 250;
    const easing = "spring()";
    let offset = window.innerWidth < 640 ? 200 : 50;

    setMessages("");

    anime({
        targets: ["#register-div"],
        translateX: ["0svw", `-${offset}svw`],
        easing: easing,
        duration: animDuration / 2,
    });
    anime({
        targets: ["#login-div"],
        translateX: [`-${offset}svw`, "0svw"],
        easing: easing,
        duration: animDuration / 2
    });
});