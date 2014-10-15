<?php
//prevent direct loading of page
if (session_id() == '') {
    die();
}
$challenges = array();
$categories = array();

$mysqli = setup_database();

$stmt = $mysqli->prepare("SELECT * from categories");
if (!$stmt->execute()) {
  die("Execute failed: Get admin for help.");
}
$res = $stmt->get_result();
while($row= $res->fetch_assoc()) {
  $categories[] = $row["category"];
}
$res->close();
$stmt->close(); 


$stmt = $mysqli->prepare("SELECT * from challenges");
if (!$stmt->execute()) {
  die("Execute failed: Get admin for help.");
}
$res = $stmt->get_result();
while($row= $res->fetch_assoc()) {
  $challenges[] = $row;
}
$res->close();
$stmt->close(); 

$mysqli->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo TITLE; ?> | Challenges</title>
<link rel="stylesheet" href="themes/<?php echo THEME;?>.css">
<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript">
ANIMATION_TIME = 200;
function updateSelectors() {
  $(".selector span").each(function() {
    if($(".challenge div.class:contains('"+$(this).text()+"')").parent().parent().is(":visible")) {
      $(this).removeClass("off").addClass("on");
    } else {
      $(this).removeClass("off").addClass("off");
    }
    if(!$(".challenge div.class:contains('"+$(this).text()+"')").length) {
      $(this).hide();
    }
  });
}
function collapseAll() {
  $(".challenge div.details").hide(ANIMATION_TIME);
}
function expandAll() {
  $(".challenge div.details").show(ANIMATION_TIME);
}
function showAll() {
  $(".challenge").show(ANIMATION_TIME);
  updateSelectors();
}
$(function() {
  updateSelectors();
  $(".selector span").addClass("on");
  $(".challenge div.title").click(function() {
    $( this ).siblings(".details").toggle(ANIMATION_TIME);
  });
  $(".selector span").click(function() {
    selection = $( this ).text();
    if($(this).hasClass("off")) {
      $(this).removeClass("off").addClass("on");
      $(".challenge div.class:contains('"+selection+"')").parent().parent().show(ANIMATION_TIME);
    } else { 
      $(this).removeClass("off").addClass("off");
      $(".challenge div.class:contains('"+selection+"')").parent().parent().hide(ANIMATION_TIME);
    }


  });
});
</script>
<?php include("head.php"); ?>
</head>

<body>
<?php include("navigation.html"); ?>
<div class="content">
  <div class="selector">
<?php foreach ($categories as $cat) { ?>
    <span><?php echo $cat; ?></span>
<?php } ?>
    <a href="javascript:collapseAll();">Collapse All</a> <a href="javascript:expandAll();">Expand All</a> <a href="javascript:showAll();">Show All</a></div>
  <div class="challenges">
<?php foreach ($challenges as $chal) { ?>
    <div class="challenge">
      <div class="title"><?php echo $chal['title']; ?> | <?php echo $chal['owner']; ?> | <?php echo calc_score($chal['count']); ?> | Solved <?php echo $chal['count'];?> times<div class="class"><?php echo $chal['category']; ?></div></div>
      <div class="details"><?php echo base64_decode($chal['hint']); ?></div>
    </div>
<?php } ?>
  </div>
</body>

</html>
