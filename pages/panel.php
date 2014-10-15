<?php
//prevent direct loading of page
if (session_id() == '') {
    die();
}
$output="";
$user_challenges = array();
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

if(isset($_POST['new-email']) && isset($_POST['new-password2']) && isset($_POST['new-password']) && isset($_POST['new-org'])) {
  $r["results"] = 1;
  $r["msg"] = "";
  //passwords must match
  if($_POST["new-password2"]!=$_POST["new-password2"]) {
    $r["results"] = 0;
    $r["msg"] .= "Fatfingered the password, try again.\n";
  }
  
  //password must be >= PASS_LEN
  if(!strlen($_POST["new-password"])>=PASS_LEN) {
    $r["results"] = 0;
    $r["msg"] .= "Password needs to be at least ".PASS_LEN." characters long.\n";
  }
  
  //check for valid email. not a perfect function but it's good enough for me
  if(!filter_var($_POST["new-email"], FILTER_VALIDATE_EMAIL)) {
    $r["results"] = 0;
    $r["msg"] .= "Invalid email.\n";
  }
  
  //check organization length
  if(strlen($_POST["new-org"])<3) {
    $r["results"] = 0;
    $r["msg"] .= "Please include what organization you are affiliated with.\n";
  }
  //check organization. Mostly just want to keep out HTML from this one. Prepared statements will take care of injections.
  if(preg_match(ORG_PATTERN, $_POST["new-org"])) {
    $r["results"] = 0;
    $r["msg"] .= "What type of organization is that? Try again using less-weird characters.\n";
  }
  if($r["results"] ==1) {
    $pass_hash = password_hash($_POST["new-password"], PASSWORD_BCRYPT);
    $stmt = $mysqli->prepare("UPDATE users set email=?, password=?, org=? WHERE name = ?");
    $stmt->bind_param("ssss", $_POST["new-email"], $pass_hash, $_POST["new-org"], $_SESSION["name"]);
    
    if (!$stmt->execute()) {
      die("Execute failed: Get admin for help.");
    }
    $stmt->close();
    $r["msg"] = "Information updated!";
  } 
} else if(isset($_POST['new-title']) && isset($_POST['new-category']) && isset($_POST['new-hint']) && isset($_POST['new-flag'])) {
  $r["results"] = 1;
  $r["msg"] = "";
  if(strlen($_POST["new-title"])<3) {
    $r["results"] = 0;
    $r["msg"] .= "Please include a longer title.\n";
  }
  //check title
  if(preg_match(TITLE_PATTERN, $_POST["new-title"])) {
    $r["results"] = 0;
    $r["msg"] .= "Please adjust your challenge title. The rules: /[^a-zA-Z0-9_\s\'\"]/ \n";
  }
  if(preg_match(CAT_PATTERN, $_POST["new-category"])) {
    $r["results"] = 0;
    $r["msg"] .= "Please use an approved category. \n";
  } else {
    foreach(explode(" ",$_POST["new-category"]) as $cat) {
      if(!in_array($cat,$categories)) {
        $r["results"] = 0;
        $r["msg"] .= $cat." is not an approved category. \n";
      }
    }
  }
  if(preg_match(FLAG_PATTERN, $_POST["new-flag"]) || strlen($_POST["new-flag"])<8) {
    $r["results"] = 0;
    $r["msg"] .= "Flags must be alphanumeric and greater than 8 characters \n";
  }
  if(strlen($_POST["new-hint"])>1125) {
    $r["results"] = 0;
    $r["msg"] .= "Your hint is too long. Limit is 1125 characters. \n";
  }
  if($r["results"] ==1) {
    $stmt = $mysqli->prepare("INSERT INTO challenges(title,owner,category,flag,hint) VALUES (?,?,?,?,?)");
    $hint = base64_encode($_POST["new-hint"]);
    $stmt->bind_param("sssss", $_POST["new-title"], $_SESSION["user"], $_POST["new-category"], $_POST["new-flag"], $hint);
    if (!$stmt->execute()) {
      die("Execute failed: Get admin for help!");
    }
    $stmt->close();
    log_activity($mysqli, "added challenge ".$_POST['new-title'], $_SESSION["user"]);
    $r["msg"] = "You challenge has been added.";
  } 
} else if(isset($_POST['edit-title']) && isset($_POST['edit-category']) && isset($_POST['edit-hint']) && isset($_POST['edit-flag']) && isset($_POST["edit-prev-title"])) {
echo $_POST['edit-category'];
  $r["results"] = 1;
  $r["msg"] = "";
  if(strlen($_POST["edit-title"])<3 || strlen($_POST["edit-prev-title"])<3) {
    $r["results"] = 0;
    $r["msg"] .= "Please include a longer title.\n";
  }
  //check title
  if(preg_match(TITLE_PATTERN, $_POST["edit-title"]) || preg_match(TITLE_PATTERN, $_POST["edit-prev-title"])) {
    $r["results"] = 0;
    $r["msg"] .= "Please adjust your challenge title. The rules: ".TITLE_PATTERN."\n";
  }
  if(preg_match(CAT_PATTERN, $_POST["edit-category"])) {
    $r["results"] = 0;
    $r["msg"] .= "Please use an approved category. \n";
  } else {
    foreach(explode(" ",$_POST["edit-category"]) as $cat) {
      if(!in_array($cat,$categories)) {
        $r["results"] = 0;
        $r["msg"] .= $cat." is not an approved category. \n";
      }
    }
  }
  if(preg_match(FLAG_PATTERN, $_POST["edit-flag"]) || strlen($_POST["edit-flag"])<8) {
    $r["results"] = 0;
    $r["msg"] .= "Flags must be alphanumeric and greater than 10 characters \n";
  }
  if(strlen($_POST["edit-hint"])>1125) {
    $r["results"] = 0;
    $r["msg"] .= "Your hint is too long. Limit is 1125 characters. \n";
  }
  if($r["results"] ==1) { 
    $stmt = $mysqli->prepare("UPDATE challenges set title=?, category=?, flag=?, hint=? WHERE owner = ? AND title=?");
    $hint = base64_encode($_POST["edit-hint"]);
    $stmt->bind_param("ssssss", $_POST["edit-title"], $_POST["edit-category"], $_POST["edit-flag"], $hint, $_SESSION["user"], $_POST["edit-prev-title"]);
    if (!$stmt->execute()) {
      die("Execute failed: Get admin for help!");
    }
    $stmt->close();
    log_activity($mysqli, "updated challenge ".$_POST['edit-prev-title'].":".$_POST['edit-title'], $_SESSION["user"]);
    $r["msg"] = "Your challenge has been updated.";
  } 
} else if(isset($_POST['del-title'])) {
  if(preg_match(TITLE_PATTERN, $_POST["del-title"])) {
    $r["results"] = 0;
    $r["msg"] .= "Flags must be alphanumeric and greater than 10 characters \n";
  } else {
    //check to see if user actually owns the challenge and is authorized to delete it. 
    $stmt = $mysqli->prepare("SELECT * from challenges where title = ? and owner = ?");
    $stmt->bind_param("ss", $_POST["del-title"], $_SESSION["user"]);
    if (!$stmt->execute()) {
      die("Execute failed: Get admin for help.");
    }
    $stmt->store_result();
    if($stmt->num_rows!=1 && $_SESSION['rank']<2) {
      $r["msg"] .= "I can't let you do that, Star Fox. (Someone else owns that challenge).";
      $r["results"] = 0;
    } else {
      $stmt->close();
      //first delete from user scores table. 
      $stmt = $mysqli->prepare("DELETE from user_scores where challenge = ? and owner = ?");
      $stmt->bind_param("ss", $_POST["del-title"], $_SESSION["user"]);
      if (!$stmt->execute()) {
        die("Execute failed: Get admin for help.");
      }
      $stmt->close();
      // now delete from challenges table. 
      $stmt = $mysqli->prepare("DELETE from challenges where title = ? and owner = ?");
      $stmt->bind_param("ss", $_POST["del-title"], $_SESSION["user"]);
      if (!$stmt->execute()) {
        die("Execute failed: Get admin for help.");
      }
      $r["results"] = 1;
      $r["msg"] = "Your challenge was successfully deleted.";
      log_activity($mysqli, "deleted challenge ".$_POST['del-title'], $_SESSION["user"]);
    }
    $stmt->close();
  }
}
if(isset($r)) {
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


$stmt = $mysqli->prepare("SELECT * from users where name= ?");
$stmt->bind_param("s", $_SESSION["user"]);

if (!$stmt->execute()) {
  die("Execute failed: Get admin for help.");
}
$res = $stmt->get_result();
if($res->num_rows == 1) {
  $user_info = $res->fetch_assoc();
}

$res->close();
$stmt->close();

$stmt = $mysqli->prepare("SELECT * from challenges where owner= ?");
$stmt->bind_param("s", $_SESSION["user"]);

if (!$stmt->execute()) {
  die("Execute failed: Get admin for help.");
}
$res = $stmt->get_result();
while($row= $res->fetch_assoc()) {
  $user_challenges[] = $row;
}
$res->close();
$stmt->close();
$mysqli->close();

// TO DO: add debugger entries.
// TO DO: fix multiple categories bug
// TO DO: add ajax support

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo TITLE; ?> | Panel</title>
<link rel="stylesheet" href="js/chosen.min.css">
<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
<link rel="stylesheet" href="themes/<?php echo THEME;?>.css">
<style type="text/css">

</style>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/chosen.jquery.min.js"></script>
<script type="text/javascript">
function showEditor(id) {
  $("#editor").children('.container').hide();
  $("#edit-"+id).show();
  $(".chosen-select-"+id).chosen({max_selected_options:4});
  $("html, body").animate({ scrollTop: $(document).height() }, 200);
}

$(function() {
 $("#editor").children('.container').hide();
  $(".chosen-select").chosen({max_selected_options:4});
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
?>

  <div class="ucp">
    <div class="par">
    <h2>Personal Info</h2>
      <div class="container">
        <form action="?p=panel" id="pers-info" method="POST">
          <div class="item">
            <label for="new-email">Email:</label>
            <input type="email" name="new-email" value="<?php echo $user_info["email"];?>">
          </div>
          <div class="item">
            <label for="new-password">New Password:</label>
            <input type="password" name="new-password">
          </div>
          <div class="item">
            <label for="new-password2">Confirm Password:</label>
            <input type="password" name="new-password2">
          </div>
          <div class="item">
            <label for="new-org">Organization:</label>
            <input type="text" name="new-org" value="<?php echo $user_info["org"];?>">
          </div>    
          <button form="pers-info" type="submit">Update</button>
        </form>
      </div>
    </div>
    
    <div class="par">
      <h2>Add New Challenge</h2>
      <div class="container">
        <form action="?p=panel" id="new-chal" method="POST">
          <div class="item">
            <label for="new-title">Challenge Title:</label>
            <input type="text" name="new-title">
          </div>
          <div class="item">
            <label for="new-category">Category</label>
            <select data-placeholder="Choose a Category" multiple class="chosen-select" name="new-category">
<?php 
foreach ($categories as $cat) {
  echo '<option value="'.$cat.'">'.$cat.'</option>';
} ?>
            </select>
          </div>
          <div class="item">
            <label for="new-flag">Flag:</label>
            <input type="textbox" name="new-flag">
          </div>
          <div class="item">
            <label for="new-hint">Hint:</label>
            <textarea name="new-hint" maxlength="1125"></textarea>
          </div>    
          <button form="new-chal" type="submit">Add</button>
        </form>
      </div>
    </div>
    <div class="par" id="editor">
      <h2>Update Challenge</h2>
        <select onchange="showEditor(value);">
          <option value=" "> </option>
<?php
  foreach ($user_challenges as $chal) {
$chal_cat = explode(" ", $chal["category"]);
  ?>
          <option value="<?php echo $chal['title']; ?>"><?php echo $chal['title']; ?></option>
  <?php
  }
?>
        </select>
<?php
$count = 0;
  foreach ($user_challenges as $chal) { ?>
        <div class="container" id="edit-<?php echo $chal['title']; ?>">
        <form action="?p=panel" method="POST" id="edit-<?php echo $count; ?>">
          <div class="item">
            <label for="edit-title">Challenge Title:</label>
            <input type="text" name="edit-title" value="<?php echo $chal['title']; ?>">
          </div>
          <div class="item">
            <label for="edit-category">Category</label>
            <select data-placeholder="Choose a Category" multiple class="chosen-select-<?php echo $chal['title']; ?>" name="edit-category" value="<?php echo $chal['category']; ?>">
<?php 
foreach ($categories as $cat) {
  echo '<option ';
  if (in_array($cat, $chal_cat)) echo "selected "; 
  echo 'value="'.$cat.'">'.$cat.'</option>';
} ?>
            </select>
          </div>
          <div class="item">
            <label for="edit-flag">Flag:</label>
            <input type="textbox" name="edit-flag" value="<?php echo $chal['flag']; ?>">
          </div>
          <div class="item">
            <label for="edit-hint">Hint:</label>
            <textarea name="edit-hint" maxlength="1125"><?php echo base64_decode($chal['hint']); ?></textarea>
          </div>      
          <input type="hidden" name="edit-prev-title" value="<?php echo $chal['title']; ?>">
          <button form="edit-<?php echo $count ?>" type="submit">Update</button>
        </form>
        <br />
        <form action="?p=panel" method="POST" id="del-<?php echo $count; ?>">
          <input type="hidden" name="del-title" value="<?php echo $chal['title']; ?>">
          <button form="del-<?php echo $count ?>" type="submit" onclick="return(confirm('Are you sure? Deleting this challenge will delete all user scores associated with it as well.'))">Delete</button>
        </form>
      </div>
        <?php
        $count++;
  }
?>
    </div>
  </div>
</div>

</body>

</html>