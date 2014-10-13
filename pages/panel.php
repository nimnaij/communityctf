<?php
//prevent direct loading of page
if (session_id() == '') {
    die();
}
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
  $(".chosen-select-"+id).chosen();
  $("html, body").animate({ scrollTop: $(document).height() }, 200);
}

$(function() {
 $("#editor").children('.container').hide();
  $(".chosen-select").chosen();
});
</script>
<?php include("head.php"); ?>
</head>

<body>
<?php include("navigation.html"); ?>

<div class="content">
  <div class="ucp">
    <div class="par">
    <h2>Personal Info</h2>
      <div class="container">
        <form action="?p=panel" id="pers-info" method="POST">
          <div class="item">
            <label for="new-email">Email:</label>
            <input type="email" name="new-email">
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
            <input type="text" name="new-org">
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
            <input type="text" name="new-titl">
          </div>
          <div class="item">
            <label for="new-category">Category</label>
            <select data-placeholder="Choose a Category" multiple class="chosen-select" name="new-category">
              <option value="Binary">Binary</option>
              <option value="Crypto">Crypto</option>
              <option value="Exploitation">Exploitation</option>
              <option value="Forensics">Forensics</option>
              <option value="Networks">Networks</option>
              <option value="Web">Web</option>
            </select>
          </div>
          <div class="item">
            <label for="new-flag">Flag:</label>
            <input type="password" name="new-flag">
          </div>
          <div class="item">
            <label for="new-hint">Hint:</label>
            <textarea name="new-hint"></textarea>
          </div>    
          <button form="new-chal" type="submit">Add</button>
        </form>
      </div>
    </div>
    
    <div class="par" id="editor">
      <h2>Update Challenge</h2>
        <select onchange="showEditor(value);">
          <option value=" "> </option>
          <option value="example">example</option>
        </select>
        <div class="container" id="edit-example">
        <form action="?p=panel" method="POST">
          <div class="item">
            <label for="new-title">Challenge Title:</label>
            <input type="text" name="new-titl">
          </div>
          <div class="item">
            <label for="new-category">Category:</label>
            <select data-placeholder="Choose a Category" multiple class="chosen-select-example" name="new-category">
              <option value="Binary">Binary</option>
              <option value="Crypto">Crypto</option>
              <option value="Exploitation">Exploitation</option>
              <option value="Forensics">Forensics</option>
              <option value="Networks">Networks</option>
              <option value="Web">Web</option>
            </select>
          </div>
          <div class="item">
            <label for="new-flag">Flag:</label>
            <input type="password" name="new-flag">
          </div>
          <div class="item">
            <label for="new-hint">Hint:</label>
            <textarea name="new-hint"></textarea>
          </div>      
          <button form="edit-chal" type="submit">Update</button>
        </form>
      </div>
    </div>
  </div>
</div>

</body>

</html>