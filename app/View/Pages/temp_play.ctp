<? $this->Html->script('monorail', false); ?>

<script type="text/javascript">
var gameObject = $.parseJSON("<?= addslashes(json_encode($game)); ?>");
var isPlayer1 = <?= $is_player_1 ?>;
var startCondition = null;
var inProcess = false;

$(document).ready(function() {
  init();
});
</script>

<div style="float: left;">
  <canvas id="canvas" width="650px" height="650px" style="background: #fff; margin:20px; "></canvas>
</div>

<div style="float: left; margin-top: 20px;">
  <div id="pieces-left">16 Tiles Left</div>

  <div style="margin-top: 20px;">
    <a href="#" id="new-straight"><img src="/img/straight_line.jpg" width="50" /></a>
  </div>

  <div style="margin-top: 20px;">
    <a href="#" id="new-curve"><img src="/img/curve.jpg" width="50" /></a>
  </div>

  <div style="margin-top: 20px;">
    <a href="#" id="turn-complete">Turn Complete</a>
  </div>

  <div style="margin-top: 20px;">
    <a href="#" id="impossible">Declare Impossible</a>
  </div>

  <div style="margin-top: 20px;">
    <a href="#" id="quit-game">Quit Game</a>
  </div>
</div>

<div style="clear: both; height: 1px;">&nbsp;</div>