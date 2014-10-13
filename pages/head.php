<?php 
//prevent direct loading of page
if (session_id() == '') {
    die();
}
?>
<script type="text/javascript">
$(function() {
  $("#user_info").html('<?php echo $_SESSION["user"];?> | <a href="?p=panel">Panel</a> | <a href="?p=logout">Logout</a>');
});
</script>