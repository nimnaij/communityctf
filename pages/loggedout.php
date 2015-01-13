<?php
//prevent direct loading of page
global $output;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" href="themes/<?php echo THEME;?>.css">
<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
<script type="text/javascript" src="js/jquery.js"></script>
</head>

<body>
<div class="navigation" style="height:60px;"><div id="logo"><a href="?">communityCTF</a></div></div>

<div class='content'>
<?php 
if($output != "") {
  echo $output;
} else { ?>
  <div class="textbox">
    <p>Welcome to communityCTF! Here, challenges aren't built by a single person, but come from the individuals. Each user is able to submit challenges; then others can compete on the challenge. The point system is self-scoring; the more times a challenge is solved, the less points it is worth.</p>
    <p><a href="?login"><strong>Login</strong></a><br /><a href="?register"><strong>Register</strong></a></p>
  </div>

<?php } ?>
</div>

</body>

</html>