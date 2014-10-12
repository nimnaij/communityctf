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
<title><?php echo TITLE; ?> | Challenges</title>
<link rel="stylesheet" href="themes/<?php echo THEME;?>.css">
<link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript">

$(function() {
ANIMATION_TIME = 200;
  $(".selector span").addClass("on");
  $(".challenge div.title").click(function() {
    $( this ).siblings(".details").toggle(ANIMATION_TIME);
  });
  $(".selector span").click(function() {
    selection = $( this ).text();
    if($(this).hasClass("off")) {
      $(this).removeClass("off").addClass("on");
      $(".challenge:contains('"+selection+"')").show(ANIMATION_TIME);
    } else { 
      $(this).removeClass("off").addClass("off");
      $(".challenge:contains('"+selection+"')").hide(ANIMATION_TIME);
    }


  });
});
</script>
</head>

<body>
<?php include("navigation.html"); ?>
<div class="content">
  <div class="selector"><span>Web</span><span>Reversing</span><span>Exploitation</span><span>Crypto</span><span>Binary</span><span>Forensics</span><span>Networking</span></div>
  <div class="challenges">
    <div class="challenge">
      <div class="title">ChalName | Author | Points | SolvedCount <div class="class">Web</div></div>
      <div class="details">Description Here<br />more description</div>
    </div>
    <div class="challenge">
      <div class="title">ChalName | Author | Points | SolvedCount <div class="class">Binary</div></div>
      <div class="details">Description Here<br />more description</div>
    </div>
    <div class="challenge">
      <div class="title">ChalName | Author | Points | SolvedCount <div class="class">Reversing</div></div>
      <div class="details">Description Here<br />more description</div>
    </div>
    <div class="challenge">
      <div class="title">ChalName | Author | Points | SolvedCount <div class="class">Web Crypto</div></div>
      <div class="details">Description Here<br />more description</div>
    </div>
    <div class="challenge">
      <div class="title">ChalName | Author | Points | SolvedCount <div class="class">Forensics</div></div>
      <div class="details">Description Here<br />more description</div>
    </div>
  </div>
</body>

</html>