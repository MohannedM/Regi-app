<?php
/**
 * Helper Functions
 */
function clean($string){
    return htmlentities($string);
}
function redirect($location){
    header("Location: $location");
}

function set_message($message){
    if(!empty($message)){
        $_SESSION['message'] = $message;
    }else{
        $message = "";
    }
}
function display_message($class = "success"){
    if(isset($_SESSION['message'])){
        $message = $_SESSION['message'];
        echo "<p class='bg-$class text-center'>$message</p>";
        unset($_SESSION['message']);
    }
}
function token_generator(){
    return $token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));
}
function validation_error($error_message){
    $error_message = <<<DELIMITER
    <div class="alert alert-warning alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <strong>Warning!</strong> $error_message
</div>
DELIMITER;
return $error_message;
}

function email_exists($email){
    $email = escape($email);
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = query($sql);
    if(row_count($result) == 1){
        return true;
    }else{
        return false;
    }
}
function username_exists($username){
    $username = escape($username);
    $sql = "SELECT id FROM users WHERE username = '$username'";
    $result = query($sql);
    if(row_count($result) == 1){
        return true;
    }else{
        return false;
    }
}
function send_message($email, $subject, $message, $header = "From: noreply@yourwebsite.com"){
    return mail($email, $subject, $message, $header);
}
/**
 * Registartion Validation Function
 */

function validate_user_registration(){
    if($_SERVER['REQUEST_METHOD'] === "POST"){
        $errors = [];
        $min = 3;
        $max = 20;
        $firstname = clean($_POST['firstname']);
        $lastname = clean($_POST['lastname']);
        $username = clean($_POST['username']);
        $email = clean($_POST['email']);
        $password = clean($_POST['password']);
        $confirm_password = clean($_POST['confirm_password']);
        $validation_code = md5($username . microtime());
        
        if(strlen($firstname) < $min){
            $errors[] = "Your firstname cannot be less than $min characters";
        }
        if(strlen($firstname) > $max){
            $errors[] = "Your firstname cannot be more than $max characters";
        }
        if(strlen($lastname) < $min){
            $errors[] = "Your lastname cannot be less than $min characters";
        }
        if(strlen($lastname) > $max){
            $errors[] = "Your lastname cannot be more than $max characters";
        }
        if(strlen($username) < $min){
            $errors[] = "Your username cannot be less than $min characters";
        }
        if(strlen($username) > $max){
            $errors[] = "Your username cannot be more than $max characters";
        }
        if(username_exists($username)){
            $errors[] = "Username is already taken";
        }
        if(email_exists($email)){
            $errors[] = "The email you entered is already taken";
        }
        if($password !== $confirm_password){
            $errors[] = "Passwords do not match";
        }

        if(!empty($errors)){
            foreach($errors as $error){
                echo validation_error($error);
            }
        }else{
            $password = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
			if(register_user($firstname, $lastname, $username, $email, $password, $validation_code)) {
				set_message("Please check your email or spam folder for activation link (for dev purposes: <a href='http://localhost/login-2/activate.php?email=$email&code=$validation_code'>Activation Link</a>)");
				redirect("index.php");
			}



		}
    }
}

/**
 * Register User Function
 */
function register_user($firstname, $lastname, $username, $email, $password, $validation_code){
    $firstname = escape($firstname);
    $lastname = escape($lastname);
    $username = escape($username);
    $email = escape($email);
    $password = escape($password);
    // $validation_code = md5($username . microtime());
    $validation_code = escape($validation_code);
    $sql = "INSERT INTO users(firstname, lastname, username, email, password, validation_code, active) ";
    $sql .= "VALUES('$firstname', '$lastname', '$username', '$email', '$password', '$validation_code', 0)";
    $result = query($sql);
    $subject = "Activation Link";
            $message = "
                Go to the following link to activate your account:
                http://localhost/login-2/activate?email=$email&code=$validation_code
                ";
    send_message($email, $subject, $message);
    return true;

}

/***
 * Activate User Function
 */
function activate_user(){
    if($_SERVER['REQUEST_METHOD'] === "GET"){
        if(isset($_GET['email']) && isset($_GET['code'])){
            $email = $_GET['email'];
            $validation_code = $_GET['code'];
            $sql = "SELECT id from users WHERE email = '$email' AND validation_code = '$validation_code'";
            $result = query($sql);
            if(row_count($result) == 1){
                $sql2 = "UPDATE users SET validation_code = 0, active = 1 WHERE email = '$email' AND validation_code = '$validation_code'";
                $result2 = query($sql2);
                set_message("Your account has been activated. Please login.");
                redirect("login.php");
            }
        }
    }
}
/**
 * Validate User Function
 */
function validate_user(){
    if($_SERVER['REQUEST_METHOD'] === "POST"){
        if(isset($_POST['email']) && isset($_POST['password'])){
            $errors = [];
            $email = clean($_POST['email']);
            $password = clean($_POST['password']);
            $email = escape($email);
            $password = escape($password);
            $sql = "SELECT id, password, username FROM users WHERE email = '$email' AND active = 1";
            $result = query($sql);
            if(row_count($result) == 1){
                $row = fetch_array($result);
                $db_password = $row['password'];
                $username = $row['username'];
                if(password_verify($password, $db_password)){
                    login_user($email, $username);
                    if(isset($_POST['remember'])){
                        $remember = clean($_POST['remember']);
                        if($remember == "on"){
                            setcookie('email', $email, time() + (60 * 15));
                            setcookie('username', $username, time() + (60 * 15));
                        }
                    }
                } else {
                    $errors[] = "Your credentials don't match";
                }

            }else{
                $errors[] = "This email doesn't exists";
            }
            
            if(!empty($errors)){
                foreach($errors as $error){
                    echo validation_error($error);
                }
            }

        }
    }
}
/**
 * Login User Function
 */
function login_user($email, $username){
    $_SESSION['email'] = $email;
    $_SESSION['username'] = $username;
    redirect("admin.php");
}

/**
 * Check is logged in function
 */
function logged_in(){
    if(isset($_SESSION['email']) || isset($_COOKIE['email'])){
        return true;
    }else{
        return false;
    }
}


/***
 * Recover Password Function
 */

 function recover_password(){
     if($_SERVER['REQUEST_METHOD'] === "POST"){
         if(isset($_SESSION['token']) && isset($_POST['token']) && isset($_POST['recover_submit'])){
             if($_SESSION['token'] === $_POST['token']){
                 $email = clean($_POST['email']);
                 $validation_code = md5(microtime() . $email);
                 if(email_exists($email)){
                     $email = escape($email);
                     $validation_code = escape($validation_code);
                    $sql = "UPDATE users SET validation_code = '$validation_code' WHERE email = '$email'";
                    $result = query($sql);
                    $subject = "Reset Link";
                    $message = "
                        Go to the following link to recover your password
                        http://localhost/login-2/code?email=$email&code=$validation_code
                        ";
                    send_message($email, $subject, $message);
                    setcookie('code', $validation_code, time() + (60 * 10));
                    set_message("Please check your email or spam folder for reset password link (for dev purposes: <a href='http://localhost/login-2/code.php?email=$email&code=$validation_code'>recover link </a>)");
                    redirect("index.php");
                 } else{
                     echo validation_error("Email doesn't exists");
                 }
             }
         }else if(isset($_POST['cancel_submit'])){
             redirect("login.php");
         }
     }
 }
 /**
  * Validate Code Function
  */
  function validate_code(){
    if(isset($_GET['code']) && isset($_GET['email'])){
        if(isset($_COOKIE['code'])){
            if($_COOKIE['code'] === $_GET['code']){
                $email = escape($_GET['email']);
                $sql = "SELECT id, validation_code FROM users WHERE email = '$email'";
                $result = query($sql);
                $row = fetch_array($result);
                $db_code = $row['validation_code'];
                if(isset($_POST['code'])){
                    if($_POST['code'] === $_COOKIE['code'] && $_POST['code'] === $db_code){
                        redirect("reset.php?email=$email&code=$db_code");
                    } else{
                        echo validation_error("Sorry, the code you entered is incorrect");
                    }
                }
            }
        }
    }else{
        redirect("index.php");
        set_message("Sorry, your recover code has expired");
    }
}
/**
 *Password Reset Function 
*/
function password_reset(){
    if(isset($_COOKIE['code'])){
        if(isset($_GET['email']) && $_GET['code']){
            if(isset($_SESSION['token']) && isset($_POST['token'])){
                if($_SESSION['token'] === $_POST['token']){
                   if(isset($_POST['reset_password_submit'])){
                       $password = clean($_POST['password']);
                       $confirm_password = clean($_POST['confirm_password']);
                       if($password === $confirm_password){
                           $password = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);
                           $password = escape($password);
                           $email = escape($_GET['email']);
                           $validation_code = escape($_COOKIE['code']);
                           $sql = "UPDATE users SET password = '$password', validation_code = 0 WHERE email = '$email' AND validation_code = '$validation_code'";
                           query($sql);
                           redirect("login.php");
                           set_message("Your password has been updated. Please login.");
                       }else{
                           echo validation_error("Passwords do not match");
                       }
                   } 
                }
            }
        }
    }else{
        redirect("index.php");
        set_message("Sorry, your recover code has expired");
    }
}

?>