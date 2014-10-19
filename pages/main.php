<?php
//prevent direct loading of page
if (session_id() == '') {
    die();
}
global $output;

$mysqli = setup_database();

$activity_feed = array();
$stmt = $mysqli->prepare("select user,description,timestamp from activity order by timestamp desc limit 25;");
if (!$stmt->execute()) {
  die("Execute failed: Get admin for help.");
}
$res = $stmt->get_result();
while($row= $res->fetch_assoc()) {
  $activity_feed[] = $row;
}
$res->close();
$stmt->close(); 

$mysqli->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo TITLE; ?> | Main</title>
<link rel="stylesheet" href="themes/<?php echo THEME;?>.css">
<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
<script type="text/javascript" src="js/jquery.js"></script>
<?php include("head.php"); ?>
</head>

<body>
<?php include("navigation.html"); ?>

<div class='content'>
<?php 
if($output != "") {
  echo $output;
} else { ?>

<div class="scoreboard">
  <h2>Activity Feed</h2>
  <?php 
foreach ($activity_feed as $activity) {
  $msg = explode("with flag",$activity["description"]);
  $msg = $activity["user"]." ".$msg[0]."; timestamp: ".$activity["timestamp"];
  echo "<span>".$msg."</span><br />";
} ?>
</div>
<?php } ?>
</div>

</body>

</html>