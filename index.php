<?php
//config

define("THEME", "default");
define("TITLE", "communityCTF");
define("DB_SERVER", "localhost");
define("DB_USER", "root");
//define("DB_USER", "ctf");
define("DB_PASS", NULL);
//define("DB_PASS", "3undienxzmfufdme");
define("DB", "communityctf");
define("PASS_COST", 5); // increase the cost for bcrypt hashing
define("PASS_LEN", 6); //min password length
define("LOGGING", True); // logs every user action in activity table
define("COOKIE", True);
define("BASE_SCORE", 1000);
// inclusions
require_once("inc/functions.php");
require_once("inc/validation.php");

//functions
function check_cookie($cookie) {
  if(!COOKIE) return false;
  if(isset($_COOKIE["track"]) && !isset($_SESSION["user"]) && !isset($_SESSION["rank"])) {
  //  if(strlen($_COOKIE["track"])!=64 || preg_match('/[^a-z0-9]/', $_COOKIE["track"])) return false;
  $cookie_status = new checkInput(array("track","track","COOKIE"));
    if($cookie_status->paramsNotSet()) return false;
  
    $mysqli = setup_database();
    
    $stmt = $mysqli->prepare("SELECT name, password, rank, session_timestamp from users WHERE session = ?");
    $stmt->bind_param("s", $_COOKIE["track"]);
    
    if (!$stmt->execute()) {
      die("Execute failed: Get admin for help.");
    }
    $res = $stmt->get_result();
    if($res->num_rows == 1) {
      $user_info = $res->fetch_assoc();
      $secret = $user_info["password"].$user_info["session_timestamp"];
      $expected = hash_hmac('sha256', $user_info["name"].$user_info["rank"], $secret);
      if(md5($expected)==md5($_COOKIE["track"])) {
        $_SESSION["user"] = $user_info["name"];
        $_SESSION["rank"] = $user_info["rank"];
        $res->close();
        $stmt->close();
        $mysqli->close();
        return true;
      } 
    }
  $res->close();
  $stmt->close();
  $mysqli->close();
  }
  return false;
}

function logout() {
  $mysqli = setup_database();
  $stmt = $mysqli->prepare("UPDATE users set session='' WHERE name = ?");
  $stmt->bind_param("s", $_SESSION["user"]);
  
  if (!$stmt->execute()) {
    echo "could not completely log you out. please notify admin that you got this error.";
  }
  $stmt->close();
  $mysqli->close();
  setcookie("track", '', time(), "/");
  unset($_COOKIE["track"]);
  session_unset(); 
  session_destroy(); 
}


function check_reg() {
  if($reg_inputs = new checkInput(array("user","user","POST"),array("pass","pass","POST"),array("pass2","pass2","POST"),array("email","email","POST"),array("org","org","POST"))) {
    $r["results"] = $reg_inputs->getStatus();
    $r["msg"] = $reg_inputs->getErrors();
    
    //passwords must match
    if($reg_inputs->getStatus()) {
      if($_POST["pass"]!=$_POST["pass2"]) {
        $r["results"] = 0;
        $r["msg"] .= "Passwords do not match, try again.\n";
      }
    }
  } else {
    $r = array('results' => 0, 'msg' => $reg_inputs->getErrors());
  }
    return $r;
}

function generate_registration_form() {
  return '<div class="ucp"><div class="par"><h2>Register</h2><div class="container"><form action="?register" id="reg" method="POST"><div class="item"><label for="user">Handle:</label><input type="text" name="user"></div><div class="item"><label for="email">Email:</label><input type="email" name="email"></div><div class="item"><label for="pass">Password:</label><input type="password" name="pass"></div><div class="item"><label for="pass2">Confirm Password:</label><input type="password" name="pass2"></div><div class="item"><label for="org">Organization:</label><input type="text" name="org"></div><button form="reg" type="submit">Register</button></form></div></div>';
}
function generate_login_form() {
  return '<div class="ucp"><div class="par"><h2>Login</h2><div class="container"><form action="?login" id="login" method="POST"><div class="item"><label for="user">Handle:</label><input type="textbox" name="user"></div><div class="item"><label for="pass">Password:</label><input type="password" name="pass"></div><button form="login" type="submit">Login</button></form></div></div>';
}

//TO DO: add email confirmation support.
function registration() {
  global $output;
  $r = check_reg();
  if($r["results"]>0) {
    $mysqli = setup_database();
    //first check if user already exists
    $stmt = $mysqli->prepare("SELECT name from users WHERE name = ?");
   
    $stmt->bind_param("s", $_POST["user"]);
    
    if (!$stmt->execute()) {
      die("Execute failed: Get admin for help.");
    }
    
    $stmt->store_result();
    if($stmt->num_rows>0) {
      $r["msg"] .= "The requested username ".$_POST['user']." already exists! Try a different one.\n";
      $r["results"] = 0;
    }
    $stmt->close();
    if($r["results"]) {
      $pass = password_hash($_POST["pass"], PASSWORD_BCRYPT);
      $date = date ("Y-m-d H:i:s", time());
      $stmt = $mysqli->prepare("INSERT INTO users(name,password,email,org,timestamp,approved) VALUES (?,?,?,?,?,1)");
      $stmt->bind_param("sssss", $_POST["user"], $pass, $_POST["email"], $_POST["org"], $date);
      if (!$stmt->execute()) {
        die("Execute failed: Get admin for help!");
      }
      $stmt->close();
      $r["msg"] .= "User successfully registered. You may now login.";
      log_activity($mysqli, "registered a new account", $_POST["user"]);
    }
    $mysqli->close();
  }
  //if we're ajax, we output json
  if(isset($_POST["ajax"])) {
    echo json_encode($r);
    die();
  }
  //otherwise, plop it in output so it can be shown on main
  if($r["results"]==1) {
    $output .="<div class='success'>".$r["msg"]."<br ></div>".generate_login_form();
  } else {
    if($r["msg"]!="") $output .="<div class='error'>".newline_to_ul_list($r["msg"])."</div>";
    $output .= generate_registration_form();
  }
}



function login() {
  global $output;
  $login_inputs = new checkInput(array("user","user","POST"),array("pass","pass","POST"));
  if($login_inputs->paramsNotSet()) {
    $output .= generate_login_form();
    return;
  }
  $r["results"] = 0;
  $r["msg"] = "Incorrect Login.\n";
  //check username for alphanumeric only

  if($login_inputs->getStatus()) {  
    $mysqli = setup_database();

    $pass = password_hash($_POST["pass"], PASSWORD_BCRYPT);
    $stmt = $mysqli->prepare("SELECT name, password, rank, approved FROM users WHERE name = ?");
    $stmt->bind_param("s", $_POST["user"]);
    
    if (!$stmt->execute()) {
      die("Execute failed: Get admin for help.");
    }
    $res = $stmt->get_result();

    if($res->num_rows == 1) {
    //TO DO: check if approved here. Currently we don't care because there's no approval system.
      $user_info = $res->fetch_assoc();
      if(password_verify($_POST["pass"],$user_info["password"])) {

        $_SESSION["user"] = $user_info["name"];
        $_SESSION["rank"] = $user_info["rank"];
        log_activity($mysqli, "logged in", $user_info["name"]);
        $r["results"] = 1;
        $r["msg"] = "";
        //set long-term cookie
        $date = date("Y-m-d H:i:s", time());
        $secret = $user_info["password"].$date;
        $cookie_string = hash_hmac('sha256', $user_info["name"].$user_info["rank"], $secret);
        
        $stmt = $mysqli->prepare("UPDATE users set session=?, session_timestamp=? WHERE name = ?");
        $stmt->bind_param("sss", $cookie_string, $date, $user_info["name"]);
        
        if (!$stmt->execute()) {
          die("Execute failed: Get admin for help.");
        }
        setcookie("track", $cookie_string, time() + (86400 * 30*7*2), "/");
      } 
    } 
    $res->close();
    $stmt->close();
    $mysqli->close();
  }
  
  if(isset($_POST["ajax"])) {
    die(json_encode($r));
  }
  
  if($r["results"]==1) {
    header("Location: ./");
    die();
  } else {
    if($r["msg"]!="") $output .="<div class='error'>".newline_to_ul_list($r["msg"])."</div>";
    $output .= generate_login_form();
  }
  
}

function check_logged_in() {
  if(isset($_SESSION["user"]) && isset($_SESSION["rank"])) {
    define("LOGGED_IN", true);
  } else if(isset($_COOKIE["track"])) {
    if(check_cookie($_COOKIE["track"])) {
      define("LOGGED_IN", true);
    } else define("LOGGED_IN", false);
  } else {
    define("LOGGED_IN", false);
  }
}

function get_page() {
  if(count($_GET)>0) {
    foreach ($_GET as $key => $code) {
      if($code=="") {
        return $key;
      }
    }
    if(isset($_GET['p'])) {
      return $_GET['p'];
    }
  } 
  return "main";
}

function load_page() {
  global $output;
  $page = get_page();

  if(LOGGED_IN) {
    switch($page){
      case 'main':
        include 'pages/main.php';
        break;
      case 'scoreboard':
        include 'pages/scoreboard.php';
        break;
      case 'panel':
        include 'pages/panel.php';
        break;
      case 'challenges':
        include 'pages/challenges.php';
        break;
      case 'logout':
        logout();
        include 'pages/loggedout.php';
        break;
      default: 
        include 'pages/main.php';
        break; 
    }
  die();
  } else {
    switch($page){
      case 'register':
        registration();
        break;
      case 'login':
        login();
        break;
      default: 

        break; 
    }
  }
}


//begin
session_start();
$output = "";
check_logged_in();
load_page();
include 'pages/loggedout.php';
?>
