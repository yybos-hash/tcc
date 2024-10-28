async function getOwner () {
    await fetch("/TCC/Project/REST/user/get-user")
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        if (status === 1) {
            owner = data.user;
            updatePfp(data.user.pfp);
        }
    });
}
async function searchUsers (search) {
    await fetch("/TCC/Project/REST/user/search-user?s=" + search)
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        if (status === 1) {
            let users = data.users;

            // remove the owner from the list
            users = users.filter((user) => user.user_hashed !== owner.user_hashed);

            let p = document.createElement("p");
            p.style.fontSize = "15pt";

            if (users.length === 1)
                p.innerHTML = users.length + " Resultado";
            else 
                p.innerHTML = users.length + " Resultados";

            resultsDiv.innerHTML = "";
            resultsDiv.appendChild(p);

            for (let i = 0; i < users.length; i++) {
                addResult(users[i]);
            }
        }
    });
}
async function friendRequest (userHash) {
    return await fetch("/TCC/Project/REST/users/friend-request", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            user: userHash
        })
    })
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        console.log(status);
        if (status === 0) {
            console.log("poop");
        }

        return status;
    });
}

function updatePfp (data) {
    for (let i = 0; i < ownerPfps.length; i++) {
        ownerPfps[i].src = "data:image/jpeg;base64," + data;
    }
}
function addResult (user) {
    const username = (user.user_name.length > 16 ? user.user_name.substr(0, 16) + "..." : user.user_name);

    const template = `
    <div class="user-result-img">
        <img loading="lazy" src="data:image/jpeg;base64,${user.pfp}" alt="pfp" onclick="goToUserProfile('${user.user_hashed}')">
    </div>

    <div class="user-result-info">
        <p class="result-info-name">${username}</p> <!-- Name -->
        <p class="result-info-class">${user.user_class} ${user.user_course}</p> <!-- class and course -->
    </div>
    `;
    
    const resultDiv = document.createElement("div");
    resultDiv.className = "user-result";
    resultDiv.innerHTML = template;
 
    // ----------------- SVG
    if (user.user_hashed !== owner.user_hashed) {
        const svg = createInviteSVG();

        const optionsDiv = document.createElement("div");
        optionsDiv.className = "user-result-options";

        // createInviteSvg returns an object
        svg.svg.addEventListener("click", async () => {
            let status = await friendRequest(user.user_hashed);
            if (status === 1) {
                svg.path1.setAttribute("fill", "#19ff1d");
                svg.path2.setAttribute("fill", "#19ff1d");    
            }
        });

        resultDiv.appendChild(optionsDiv);
        optionsDiv.appendChild(svg.svg);
    }

    // add an opacity animation
    anime({
        targets: resultDiv,
        opacity: [0, 1],
        duration: 750
    }); 

    resultsDiv.appendChild(resultDiv);
    results.push(resultDiv);
}

function createInviteSVG () {
    // namespace thing
    const ns = "http://www.w3.org/2000/svg";

    const svg = document.createElementNS(ns, "svg");
    svg.setAttribute("xmlns", "http://www.w3.org/2000/svg");
    svg.setAttribute("version", "1.1");
    svg.setAttribute("viewBox", "0 0 100 100");
    svg.setAttribute("style", "shape-rendering:geometricPrecision; text-rendering:geometricPrecision; fill-rule:evenodd; clip-rule:evenodd; width: 30px; height: 30px;");
    svg.setAttribute("xmlns:xlink", "http://www.w3.org/1999/xlink");

    const g1 = document.createElementNS(ns, "g");
    const g2 = document.createElementNS(ns, "g");

    const path1 = document.createElementNS(ns, "path");
    path1.setAttribute("style", "opacity:1");
    path1.setAttribute("fill", "#ffffff");
    path1.setAttribute("d", `M -0.5,13.5 C 32.8333,13.5 66.1667,13.5 99.5,13.5C 99.5,37.5 99.5,61.5 99.5,85.5C 66.1667,85.5 32.8333,85.5 -0.5,85.5C -0.5,61.5 -0.5,37.5 -0.5,13.5 Z M 3.5,17.5 C 34.1667,17.5 64.8333,17.5 95.5,17.5C 95.5,38.8333 95.5,60.1667 95.5,81.5C 64.8333,81.5 34.1667,81.5 3.5,81.5C 3.5,60.1667 3.5,38.8333 3.5,17.5 Z`);

    const path2 = document.createElementNS(ns, "path");
    path2.setAttribute("style", "opacity:1");
    path2.setAttribute("fill", "#ffffff");
    path2.setAttribute("d", `M 7.5,26.5 C 21.4726,34.4882 35.4726,42.4882 49.5,50.5C 63.4151,42.6263 77.2484,34.6263 91,26.5C 91.8141,28.9018 91.6474,31.2351 90.5,33.5C 76.6716,40.9133 63.0049,48.58 49.5,56.5C 35.9951,48.58 22.3284,40.9133 8.5,33.5C 7.52577,31.2573 7.19244,28.924 7.5,26.5 Z`);

    g1.appendChild(path1);
    g2.appendChild(path2);

    svg.appendChild(g1);
    svg.appendChild(g2);

    return {
        svg: svg,
        path1: path1,
        path2: path2
    };
}

function goToUserProfile (hash) {
    window.location.href = "user?u=" + hash;
}