<?php
//prevent direct loading of page
if (session_id() == '') {
    die();
}
$output="";
$user_challenges = array();
$categories = array();
$pending_users = array();
$pending_chals = array();
$challenge_status = array(-1 => "Disapproved.", 0 => "Waiting for moderator.", 1=> "Approved.");
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

if($_SESSION["rank"]>0) {
  $moderate_chal_inputs = new checkInput(array("title","moderate-title","POST"),array("user","moderate-owner","POST"),array("flag","action","POST"));
  if($moderate_chal_inputs->getStatus()) {
    $r["results"] = 1;

    if($_POST["action"]=="disapprove") {
      $chal_approval = -1;
      $r["msg"] = "Challenge denied.\n";
    } else if($_POST["action"]=="approve") {
      $r["msg"] = "Challenge approved.\n";
      $chal_approval = 1;
    } else {
      $r["msg"] = "No. n000b.\n";
      $chal_approval = 0;
      $r["results"] = 0;
    }
    if($chal_approval!=0) {
      $stmt = $mysqli->prepare("UPDATE challenges set approved=? WHERE owner = ? AND title=?");
      $stmt->bind_param("iss", $chal_approval, $_POST["moderate-owner"], $_POST["moderate-title"]);
      if (!$stmt->execute()) {
        die("Execute failed: Get admin for help!");
      }
      $stmt->close();
      log_activity($mysqli, "moderated challenge ".$_POST['moderate-title'].":".$_POST['moderate-owner']." to:".$chal_approval, $_SESSION["user"]);
    }
  } else if(($moderate_account_input = new checkInput(array("user","mod-user","POST"),
      array("flag","action","POST"))) && 
      !$moderate_account_input->paramsNotSet() {
        
      }
} 


if($_SESSION["rank"]==1) {

  $stmt = $mysqli->prepare("select name, email, org from users where approved = 1");
  if (!$stmt->execute()) {
    die("Execute failed: Get admin for help.");
  }
  $res = $stmt->get_result();
  while($row= $res->fetch_assoc()) {
    $pending_users[] = $row;
  }
  $res->close();
  $stmt->close();
  
  $stmt = $mysqli->prepare("select title, owner, hint, category, approved from challenges where approved = 0");
  if (!$stmt->execute()) {
    die("Execute failed: Get admin for help.");
  }
  $res = $stmt->get_result();
  while($row= $res->fetch_assoc()) {
    $pending_chals[] = $row;
  } 
} else if($_SESSION["rank"]==2) {

}

if(($update_account_input = new checkInput(array("email","new-email","POST"),
      array("pass","new-password","POST"),
      array("pass2","new-password2","POST"),
      array("org","new-org","POST"))) && 
      !$update_account_input->paramsNotSet()) {
  $r["results"] = $update_account_input->getStatus();
  $r["msg"] = $update_account_input->getErrors();
  //passwords must match
  if($_POST["new-password2"]!=$_POST["new-password2"]) {
    $r["results"] = 0;
    $r["msg"] .= "Fatfingered the password, try again.\n";
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
} else if(($newchal_input = new checkInput(array("title","new-title","POST"),
      array("cat","new-category","POST"),
      array("hint","new-hint","POST"),
      array("flag","new-flag","POST"))) && 
      !$newchal_input->paramsNotSet()) {

  $r["results"] = $newchal_input->getStatus();
  $r["msg"] = $newchal_input->getErrors();
  
  foreach(explode(" ",$_POST["new-category"]) as $cat) {
    if(!in_array($cat,$categories)) {
      $r["results"] = 0;
      $r["msg"] .= $cat." is not an approved category. \n";
    }
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
} else if(($editchal_input = new checkInput(array("title","edit-title","POST"),
      array("title","edit-prev-title","POST"),
      array("cat","edit-category","POST"),
      array("hint","edit-hint","POST"),
      array("flag","edit-flag","POST"))) && 
      !$editchal_input->paramsNotSet()) {
  $r["results"] = $editchal_input->getStatus();
  $r["msg"] = $editchal_input->getErrors();
  
  foreach(explode(" ",$_POST["edit-category"]) as $cat) {
    if(!in_array($cat,$categories)) {
      $r["results"] = 0;
      $r["msg"] .= $cat." is not an approved category. \n";
    }
  }
  if($r["results"] ==1) { 
    $stmt = $mysqli->prepare("UPDATE challenges set title=?, category=?, flag=?, hint=?, approved=0 WHERE owner = ? AND title=?");
    $hint = base64_encode($_POST["edit-hint"]);
    $stmt->bind_param("ssssss", $_POST["edit-title"], $_POST["edit-category"], $_POST["edit-flag"], $hint, $_SESSION["user"], $_POST["edit-prev-title"]);
    if (!$stmt->execute()) {
      die("Execute failed: Get admin for help!");
    }
    $stmt->close();
    log_activity($mysqli, "updated challenge ".$_POST['edit-prev-title'].":".$_POST['edit-title'], $_SESSION["user"]);
    $r["msg"] = "Your challenge has been updated.";
  } 
} else if(($del_input = new checkInput(array("title","del-title","POST"))) && $del_input->getStatus()) {
    $r["results"] = $del_input->getStatus();
    $r["msg"] .= $del_input->getErrors();
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
if(isset($r)) {
  if(isset($_POST['ajax'])) {
    echo json_encode($r);
    die();
  }
  if($r["results"] == 1 && $r["msg"]!="") {
    $output .="<div class='success'>".$r["msg"]."</div>";
  } else if($r["msg"]!="") {
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

// TO DO: add challenge dependencies support
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
<?php if($_SESSION["rank"]>0) { ?><script src="js/jquery-ui.min.js"></script><?php } ?>
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
 $("html, body").animate({ scrollTop:0 }, 50);
 $(".chosen-select").chosen({max_selected_options:4});
<?php if($_SESSION["rank"]>0) { ?> $( "#panel_tabs" ).tabs(); <?php } ?>
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
<?php if($_SESSION["rank"]>0) { ?>
  <div id="panel_tabs">
    <ul class="panel_tabs">
      <li><a href="#ucp">User Panel</a></li>
      <li><a href="#mcp">Moderator Panel</a></li>
  <?php   if($_SESSION["rank"]>1) { ?>      <li><a href="#acp">Admin Panel</a></li> <?php } ?>
    </ul>  
  <?php   if($_SESSION["rank"]>1) { ?>
  <div class="ucp" id="acp">
    <div class="par">
    <h2>Website Manager</h2>
    </div>
    <div class="par">
    <h2>Challenge Manager</h2>
    </div>
    <div class="par">
    <h2>User Manager</h2>
    </div>
    <div class="par">
    <h2>Manage Categories</h2>
    </div>
  </div>
  <?php   } ?>
    <div class="ucp" id="mcp">
      <div class="par">
      <h2>Challenge Approval</h2>
        <div class="container">
        <?php if(sizeof($pending_chals)==0) {
          echo "No challenges to approve.";
        } else {
        $count = 0;
        foreach ($pending_chals as $chal) { ?>
        <div class="container" id="moderate-<?php echo $chal['title']; ?>">

          <div class="item">
            Challenge Title:
            <span><?php echo $chal['title']; ?></span>
          </div>
          <div class="item">
            Category:
            <span><?php echo $chal['category']; ?></span>
          </div>
          <div class="item">
            Hint:
            <span class="html-source"><?php echo htmlentities(base64_decode($chal['hint'])); ?></span>
          </div>
          <form action="?p=panel#mcp" method="POST" id="moderate-<?php echo "app-".$count; ?>">
          <input type="hidden" name="moderate-title" value="<?php echo $chal['title']; ?>">
          <input type="hidden" name="moderate-owner" value="<?php echo $chal['owner']; ?>">
          <input type="hidden" name="action" value="approve">
          <button form="moderate-<?php echo "app-".$count ?>" type="submit">Approve</button>
        </form>
        <form action="?p=panel#mcp" method="POST" id="moderate-<?php echo "den-".$count; ?>">
          <input type="hidden" name="moderate-title" value="<?php echo $chal['title']; ?>">
          <input type="hidden" name="moderate-owner" value="<?php echo $chal['owner']; ?>">
          <input type="hidden" name="action" value="disapprove">
          <button form="moderate-<?php echo "den-".$count ?>" type="submit">Disapprove</button>
        </form>
        <br />
      </div>
        <?php
        $count++;
  }

        } ?>
        </div>
      </div>
      <div class="par">
      <h2>User Approval</h2>
        <div class="container">
        <?php if(sizeof($pending_users)==0) {
          echo "No users to approve.";
        } else {
          print_r($pending_users);
        } ?>
        </div>

      </div>
    </div>
<?php } ?>
  <div class="ucp" id="ucp">
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
<?php // TODO: make this more efficient. only loop once, not every time.
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
          <div class="item">
            <strong>Status: </strong>
            <?php echo $challenge_status[$chal['approved']]; ?>
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
<?php if($_SESSION["rank"] >0) { echo "</div>"; } ?>
</div>

</body>

</html>