<?php
//prevent direct loading of page
if (session_id() == '') {
    die();
}
global $output;
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


<?php } ?>
</div>

</body>

</html>