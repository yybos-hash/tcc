<?php
    $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
        $root = "/TCC/Project/REST";

        $r->addRoute("GET", "{$root}", ["HomeController", "getView"]);
        $r->addRoute("GET", "{$root}/home", ["HomeController", "getView"]);

        $r->addRoute("GET", "{$root}/auth", ["LoginController", "getView"]);
        $r->addRoute("POST", "{$root}/auth/login", ["LoginController", "login"]);
        $r->addRoute("POST", "{$root}/auth/register", ["LoginController", "register"]);
        $r->addRoute("GET", "{$root}/auth/token", ["LoginController", "getToken"]);
        $r->addRoute("POST", "{$root}/auth/logout", ["LoginController", "logout"]);

        $r->addRoute("GET", "{$root}/chat", ["ChatController", "getView"]);
        $r->addRoute("GET", "{$root}/chat/get/chats", ["ChatController", "getChats"]);
        $r->addRoute("GET", "{$root}/chat/get/messages", ["ChatController", "getMessages"]);
               
        $r->addRoute("GET", "{$root}/owner", ["UserController", "getOwnerView"]);
        $r->addRoute("GET", "{$root}/user", ["UserController", "getUserView"]);
        $r->addRoute("GET", "{$root}/user/get/user", ["UserController", "getUserInfo"]);
        $r->addRoute("POST", "{$root}/user/update/user", ["UserController", "updateUserInfo"]);
        $r->addRoute("GET", "{$root}/user/search/user", ["UserController", "searchUser"]);
        $r->addRoute("POST", "{$root}/user/delete/user", ["UserController", "deleteAccount"]);
        $r->addRoute("GET", "{$root}/user/get/friends", ["UserController", "getFriends"]);
        $r->addRoute("GET", "{$root}/user/get/pending-friends", ["UserController", "getPendingFriends"]);
        $r->addRoute("POST", "{$root}/user/accept-friendship", ["UserController", "acceptFriendship"]);
        $r->addRoute("POST", "{$root}/user/upload/pfp", ["UserController", "uploadUserPicture"]);
        $r->addRoute("GET", "{$root}/user/get/pfp", ["UserController", "getUserPicture"]); 
        $r->addRoute("POST", "{$root}/user/upload/background", ["UserController", "uploadUserBackground"]);
        $r->addRoute("GET", "{$root}/user/get/background", ["UserController", "getUserBackground"]); 

        $r->addRoute("GET", "{$root}/users", ["UsersController", "getView"]);
        $r->addRoute("POST", "{$root}/users/friend-request", ["UsersController", "friendRequest"]);
        
        $r->addRoute("GET", "{$root}/reset", ["ResetController", "getView"]);
        $r->addRoute("POST", "{$root}/reset/password", ["ResetController", "resetRequest"]);

        /*
        POST auth/login
        POST auth/register
        POST auth/logout (invalidate token)

        GET user/{id} (get user info)
        GET chat/messages/{conversation} (get the list of messages from a conversation)
        */
    });
?>