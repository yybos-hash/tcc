<?php 
require_once "Controller.php";

class HomeController extends Controller {
    public function getView () {
        include "View/Home/index.html";
    }
}
?>