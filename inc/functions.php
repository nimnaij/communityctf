<?php

// getting the bcrypt stuff: http://stackoverflow.com/questions/4795385/how-do-you-use-bcrypt-for-hashing-passwords-in-php
if(version_compare(phpversion(), '5.5', '<')) {
  require_once("password.php");
}

function setup_database() {
$mysqli = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB);
if ($mysqli->connect_errno) {
    die("Let the admin know there is a database problem. I'm just gonna die here.");
}

return $mysqli;
}
function log_activity($mysqli, $activity, $user="") {
  if(!LOGGING) return;
  $date = date ("Y-m-d H:i:s", time());
  $stmt = $mysqli->prepare("INSERT INTO activity(ip,description,timestamp,user) VALUES (?,?,?,?)");
  $stmt->bind_param("ssss", $_SERVER['REMOTE_ADDR'], $activity, $date,$user);
  if (!$stmt->execute()) {
    //TO DO: figure out what to do when this fails.
    echo "logging error. please get admin.";
  }
  $stmt->close();
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
function calc_score($count) {
$base = BASE_SCORE;
  if($count<1) {
    return $base;
  }
  return $base/$count;
}

//regexps
define('ORG_PATTERN', '/[^a-zA-Z0-9_\s\'\"]/');
define('TITLE_PATTERN','/[^a-zA-Z0-9_]/');
define('FLAG_PATTERN', '/[^a-zA-Z0-9\s"]/');
define('CAT_PATTERN', '/[^a-zA-Z0-9\s]/');
define('USER_PATTERN', '/[^a-zA-Z0-9_]/');

?>
