<?php
//config

define("THEME", "default");
define("TITLE", "communityCTF");
define("DB_SERVER", "localhost");
define("DB_USER", "root");
define("DB_PASS", NULL);
define("DB", "communityctf");
define("PASS_COST", 5); // increase the cost for bcrypt hashing
define("PASS_LEN", 6); //min password length
define("LOGGING", True); // logs every user action in activity table
define("COOKIE", True);

// inclusions
require_once("inc/functions.php");


//functions
//TO DO: implement cookie lookup in DB
function check_cookie($cookie) {
  if(!COOKIE) return false;
  if(isset($_COOKIE["track"]) && !isset($_SESSION["user"]) && !isset($_SESSION["rank"])) {
    if(strlen($_COOKIE["track"])!=64 | preg_match('/[^a-z0-9]/', $_COOKIE["track"])) return false;
  
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
        $_Session["rank"] = $user_info["rank"];
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
  unset($_COOKIE["track"]);
  setcookie("track", '', time() - 3600);
  unset($_SESSION["user"]);
  unset($_SESSION["rank"]);
  session_unset(); 
  session_destroy(); 
  session_write_close();
  setcookie(session_id(), "", time() - 3600);
  header("Cache-Control: no-cache, must-revalidate");
}

function newline_to_ul_list($str) {
  $out = "<ul>\n";
  $lines = explode("\n",$str);
  foreach ($lines as $line) {
    if($line !="") $out .="<li>".$line."</li>\n";
  }
  $out .="</ul>\n";
  return $out;
}

function check_reg() {
  if(isset($_POST["user"]) && isset($_POST["pass"]) && isset($_POST["pass2"]) && isset($_POST["email"]) && isset($_POST["org"])) {
    $results["results"] = 1;
    $results["msg"] = "";
    
    //check username length
    if(!strlen($_POST["user"])>0) {
      $results["results"] = 0;
      $results["msg"] .= "Username must be at least 1 character long.\n";
    }
    
    //check username for alphanumeric only
    if(preg_match('/[^a-zA-Z0-9_]/', $_POST["user"])) {
      $results["results"] = 0;
      $results["msg"] .= "This is America, only alphanumeric and underscores allowed.\n";
    }
    
    //passwords must match
    if($_POST["pass"]!=$_POST["pass2"]) {
      $results["results"] = 0;
      $results["msg"] .= "Fatfingered the password, try again.\n";
    }
    
    //password must be >= PASS_LEN
    if(!strlen($_POST["pass"])>=PASS_LEN) {
      $results["results"] = 0;
      $results["msg"] .= "Password needs to be at least ".PASS_LEN." characters long.\n";
    }
    
    //check for valid email. not a perfect function but it's good enough for me
    if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
      $results["results"] = 0;
      $results["msg"] .= "Invalid email.\n";
    }
    
    //check organization length
    if(strlen($_POST["org"])<3) {
      $results["results"] = 0;
      $results["msg"] .= "Please include what organization you are affiliated with.\n";
    }
    //check organization. Mostly just want to keep out HTML from this one. Prepared statements will take care of injections.
    if(preg_match('/[^a-zA-Z0-9_\s\'\"]/', $_POST["org"])) {
      $results["results"] = 0;
      $results["msg"] .= "What type of organization is that? Try again using less-weird characters.\n";
    }
    
  } else {
    if(!(isset($_POST["user"]) || isset($_POST["pass"]) && isset($_POST["pass2"]) || isset($_POST["email"]) || isset($_POST["org"]))) {
      $results = array('results' => -1, 'msg' => "");
    } else $results = array('results' => 0, 'msg' => "All fields are required.");
  }
    return $results;
}

function generate_registration_form() {
  return '<div class="ucp"><div class="par"><h2>Register</h2><div class="container"><form action="?p=register" id="reg" method="POST"><div class="item"><label for="user">Handle:</label><input type="text" name="user"></div><div class="item"><label for="email">Email:</label><input type="email" name="email"></div><div class="item"><label for="pass">Password:</label><input type="password" name="pass"></div><div class="item"><label for="pass2">Confirm Password:</label><input type="password" name="pass2"></div><div class="item"><label for="org">Organization:</label><input type="text" name="org"></div><button form="reg" type="submit">Register</button></form></div></div>';
}
function generate_login_form() {
  return '<div class="ucp"><div class="par"><h2>Login</h2><div class="container"><form action="?p=login" id="login" method="POST"><div class="item"><label for="user">Handle:</label><input type="textbox" name="user"></div><div class="item"><label for="pass">Password:</label><input type="password" name="pass"></div><button form="login" type="submit">Login</button></form></div></div>';
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
      $stmt = $mysqli->prepare("INSERT INTO users(name,password,email,org,timestamp) VALUES (?,?,?,?,?)");
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
    echo json_encode($results);
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


//TO DO: implement long term session cookie
function login() {
  global $output;
  if(!isset($_POST["user"]) && !isset($_POST["pass"])) {
    $output .= generate_login_form();
    return;
  }
  $results["results"] = 0;
  $results["msg"] = "Incorrect Login.\n";
  //check username for alphanumeric only

  if(!preg_match('/[^a-zA-Z0-9_]/', $_POST["user"])) {  
    $mysqli = setup_database();

    $pass = password_hash($_POST["pass"], PASSWORD_BCRYPT);
    $stmt = $mysqli->prepare("SELECT name, password, rank, approved FROM users WHERE name = ?");
    $stmt->bind_param("s", $_POST["user"]);
    
    if (!$stmt->execute()) {
      die("Execute failed: Get admin for help.");
    }
    $res = $stmt->get_result();
    echo "query executed";

    if($res->num_rows == 1) {
    //TO DO: check if approved here. Currently we don't care because there's no approval system.
      $user_info = $res->fetch_assoc();
      if(password_verify($_POST["pass"],$user_info["password"])) {
        echo "should be logged in here...";
        $_SESSION["user"] = $user_info["name"];
        $_SESSION["rank"] = $user_info["rank"];
        log_activity($mysqli, "logged in", $user_info["rank"]);
        $results["results"] = 1;
        $results["msg"] = "";
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
      } else echo "passwords do not match!";
    } else echo "wrong number of rows";
    $res->close();
    $stmt->close();
    $mysqli->close();
  }
  
  if(isset($_POST["ajax"])) {
    echo json_encode($results);
    die();
  }
  
  if($results["results"]==1) {
    header("Location: ./");
    die();
  } else {
    if($results["msg"]!="") $output .="<div class='error'>".newline_to_ul_list($results["msg"])."</div>";
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

function load_page() {
  global $output;
  if(isset($_GET['p'])) {
    $page = $_GET['p'];
  } else $page = "main";

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