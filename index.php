<?php
//config

define("THEME", "default");
define("TITLE", "communityCTF");

//functions
function load_page() {
  if(isset($_GET['p'])) {
    $page = $_GET['p'];
  } else $page = "main";
  
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
    default: 
      include 'pages/main.php';
      break; 
  }
}


//begin
session_start();

load_page();
?>