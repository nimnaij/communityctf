<?php
//prevent direct loading of page
if (session_id() == '') {
    die();
}
$challenges = array();
$categories = array();

function gen_submission_form($chalid, $owner) { ?>
      <form class="sub-form" method="POST" action="?p=challenges" id="sub-<?php echo $chalid."-".$owner;?>">
        <input type="hidden" name="sub-chal" value="<?php echo $chalid; ?>">
        <input type="hidden" name="sub-owner" value="<?php echo $owner;?>">
        <input type="textbox" value="" name="sub-flag">
        <input type="submit" value="Submit">
      </form><?php
}



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


  $r["results"] = 0;
  $r["msg"] = "";
if(isset($_POST['sub-chal']) && isset($_POST['sub-owner']) && isset($_POST['sub-flag'])) {
  if($_POST["sub-owner"]==$_SESSION["user"]) {
    $r["msg"] .= "You cannot submit a flag for your own challenge.\n";
  } else if (preg_match(TITLE_PATTERN, $_POST["sub-chal"] || preg_match(USER_PATTERN, $_POST["sub-owner"]))){
    $r["msg"] .= "Stop tampering with parameters.\n";
  } else if (preg_match(FLAG_PATTERN, $_POST["sub-flag"])) {
    $r["msg"] .= "Invalid flag.";
  } else {
    $stmt = $mysqli->prepare("SELECT * from challenges WHERE owner = ? AND title = ? AND flag = ?");
    $stmt->bind_param("sss", $_POST["sub-owner"], $_POST["sub-chal"], $_POST["sub-flag"]);
    if (!$stmt->execute()) {
      die("Execute failed: Get admin for help.");
    }
    $res = $stmt->get_result();
    if($res->num_rows == 1) {
      $res->close();
      $stmt->close();
      //success, update DB that user has scored.
      $r["msg"] = "Your flag is correct!\n";
      $r["results"] = 1;

      
      $stmt = $mysqli->prepare("UPDATE challenges set count = count +1 WHERE owner = ? AND title=? ");
      $stmt->bind_param("ss", $_POST["sub-owner"], $_POST["sub-chal"]);
      if (!$stmt->execute()) {
        die("Execute failed: Get admin for help.");
      }
      $stmt->close();
      
      $stmt = $mysqli->prepare("INSERT INTO user_scores(user,challenge,owner,timestamp,ip) VALUES (?,?,?,?,?)");
      $date = date ("Y-m-d H:i:s", time());
      $stmt->bind_param("sssss", $_SESSION["user"], $_POST["sub-chal"], $_POST["sub-owner"], $date, $_SERVER['REMOTE_ADDR']);
      if (!$stmt->execute()) {
        die("Execute failed: Get admin for help.");
      }
      log_activity($mysqli, "scored on challenge ".$_POST['sub-chal'].":".$_POST['sub-owner']." with flag '".$_POST["sub-flag"]."'", $_SESSION["user"]);
    } else {
      $res->close();
      $stmt->close();
      $r["msg"] = "Your flag is wrong.\n";
      log_activity($mysqli, "attempted on challenge ".$_POST['sub-chal'].":".$_POST['sub-owner']." with flag ".$_POST["sub-flag"]."'", $_SESSION["user"]);
    }
  }
   
  if(isset($_POST['ajax'])) {
    echo json_encode($r);
    die();
  }
  if($r["results"] == 1 && $r["msg"]!="") {
    $output .="<div class='success'>".$r["msg"]."</div>";
  } else {
    $output .="<div class='error'>".newline_to_ul_list($r["msg"])."</div>";
  }
}
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
<?php
if($output != "") {
  echo $output;
} 
?><br />
  <div class="selector">
<?php foreach ($categories as $cat) { ?>
    <span><?php echo $cat; ?></span>
<?php } ?>
    <a href="javascript:collapseAll();">Collapse All</a> <a href="javascript:expandAll();">Expand All</a> <a href="javascript:showAll();">Show All</a></div>
  <div class="challenges">
<?php foreach ($challenges as $chal) { ?>
    <div class="challenge">
      <div class="title"><?php echo $chal['title']; ?> | <?php echo $chal['owner']; ?> | <?php echo calc_score($chal['count']); ?> | Solved <?php echo $chal['count'];?> times<div class="class"><?php echo $chal['category']; ?></div></div>
      <div class="details"><?php echo base64_decode($chal['hint']); ?>
      <br /><?php 
if($chal['owner']!= $_SESSION['user']) {
  gen_submission_form($chal['title'], $chal['owner']);
  }
  ?>
      </div>
    </div>
<?php } ?>
  </div>
</body>

</html>
