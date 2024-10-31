<?php 
    require_once "autoRequire.php";

    error_reporting(E_ERROR | E_PARSE);

    $uri_request = $_SERVER["REQUEST_URI"]; 
    $http_request = $_SERVER["REQUEST_METHOD"];

    // Strip query string (?foo=bar) and decode URI
    if (false !== $pos = strpos($uri_request, '?'))
        $uri_request = substr($uri_request, 0, $pos);

    $uri_request = rawurldecode($uri_request);

    $routeInfo = $dispatcher->dispatch($http_request, $uri_request);
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            // 404 Not Found
            echo "404 URI não encontrada.";

            break;
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $allowedMethods = $routeInfo[1];
            // 405 Method Not Allowed
            echo "405 O método HTTP usado -> {$http_request} não é suportado.";   
        
            break;
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];

            $classe = $handler[0];
            $function = $handler[1];

            // nasty hack (transform variable in an object and another variable in a method of that object)
            $controller = new $classe();
            $controller->{$function}(); // $_GET and $_POST are global variables as they can be accessed from anywhere in the code as long as the file is included

            break;
    }
?>