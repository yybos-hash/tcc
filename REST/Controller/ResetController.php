<?php 
require_once "Controller.php";

class ResetController extends Controller {
    public function __construct () {
        parent::__construct();
    }
    
    public function getView () {
        include "View/Reset/index.html";
    }
}
?>