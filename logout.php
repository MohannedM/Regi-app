<?php
include "functions/init.php";
if(isset($_COOKIE['email'])){
    unset($_COOKIE['email']);
    setcookie('email', null, time() - (60 * 15));
    unset($_COOKIE['username']);
    setcookie('username', null, time() - (60 * 15));
}
session_destroy();
redirect("index.php");

?>