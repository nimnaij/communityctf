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
</head>

<body>
<?php include("navigation.html"); ?>
<div class="content">
  <div class="selector"><span>Web</span><span>Reversing</span><span>Exploitation</span><span>Crypto</span><span>Binary</span><span>Forensics</span><span>Networking</span><a href="javascript:collapseAll();">Collapse All</a> <a href="javascript:expandAll();">Expand All</a> <a href="javascript:showAll();">Show All</a></div>
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
    <div class="challenge">
      <div class="title">ChalName | Author | Points | SolvedCount <div class="class">Networking Forensics</div></div>
      <div class="details">Description Here<br />more description</div>
    </div>
  </div>
</body>

</html>