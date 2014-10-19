<?php
//prevent direct loading of page
if (session_id() == '') {
    die();
}

$mysqli = setup_database();

$stmt = $mysqli->prepare("select users.name,users.rank,users.org,SUM(".BASE_SCORE."/challenges.count) as 'score' from users left join user_scores on users.name = user_scores.user left join challenges on user_scores.challenge = challenges.title and user_scores.owner = challenges.owner where challenges.count>0 group by users.name order by score DESC, user_scores.timestamp ASC");
if (!$stmt->execute()) {
  die("Execute failed: Get admin for help.");
}
$res = $stmt->get_result();
while($row= $res->fetch_assoc()) {
  $scoreboard[] = $row;
}
$res->close();
$stmt->close(); 
$mysqli->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo TITLE; ?> | Scoreboard</title>
<link rel="stylesheet" href="themes/<?php echo THEME;?>.css">
<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
<script type="text/javascript" src="js/jquery.js"></script>
<?php include("head.php"); ?>
</head>
<body>
<?php include("navigation.html"); ?>

<div class="scoreboard">
  <table>
    <tr class="title">
      <td>Place</td><td>Name</td><td>Branch</td><td>Score</td>
    </tr>
<?php 
$count = 1;
foreach ($scoreboard as $row) { ?>
    <tr>
      <td><?php echo $count;?></td><td><?php echo $row["name"];?></td><td><?php echo $row["org"];?></td><td><?php echo $row["score"];?></td>
    </tr><?php 
    $count++;} ?>
  </table>
</div>

</body>

</html>
