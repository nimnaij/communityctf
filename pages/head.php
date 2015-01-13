<?php 
//prevent direct loading of page
if (session_id() == '') {
    die();
}
?>
<script type="text/javascript">
$(function() {
  $("#user_info").html('<?php echo $_SESSION["user"];?> | <a href="?panel">Panel</a> | <a href="?logout">Logout</a>');
});
</script>