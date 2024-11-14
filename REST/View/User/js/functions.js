async function getUser (hash) {
    let url = "/TCC/Project/REST/user/get-user";

    if (hash !== null) {
        url += ("?u=" + hash);
    }

    return await fetch(url)
    .then(response => response.json())
    .then(data => {
        var status = data.status;

        console.log(status);

        if (status === 0) {
            console.log("fuck");
        }
        else if (status === 1) {
            let user = data.user;
            return user;
        }
    })
    .catch(error => console.log(error.message));
}