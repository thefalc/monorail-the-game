<? $this->Html->css('animate', null, array("inline"=>false)); ?>
<? $this->Html->script('wow.min', false); ?>
<? $this->Html->script('monorail', false); ?>

<script type="text/javascript">
  var gameObject = $.parseJSON("<?= addslashes(json_encode($game)); ?>");
  var isPlayer1 = <?= $is_player_1 ?>;
  var startCondition = null;
  var inProcess = false;

	$(document).ready(function() {
    new WOW().init();

    $("#quit-game").click(function(e) {
      e.preventDefault();

      quitGame();
    });

    $("#how-to-play-btn").click(function(e) {
      e.preventDefault();

      showRules();
    });

    $("#close-how-to-btn").click(function(e) {
      e.preventDefault();

      $("#how-to-play").hide();
    });

    $("#join-game").click(function(e) {
      e.preventDefault();

      $("#newGameModal").modal("show"); 
    });

    $("#zoom-in").click(function(e) {
      e.preventDefault();

      game.zoomIn();
    });

    $("#zoom-out").click(function(e) {
      e.preventDefault();

      game.zoomOut();
    });

    $("#chat-link").click(function(e) {
      e.preventDefault();

      saveChatText();
    });

    $("#chat-text").keypress(function(e) {
        if(e.keyCode == 13) {
            e.preventDefault();
            
            saveChatText();
        }
    });

    init();    
  });

  function saveChatText() {
    if($.trim($("#chat-text").val())) {
      $("#chat-link").button("loading");
      $.post("/pages/saveChat", $("#chat-form").serialize(), function(response) {
        $("#chat-text").val("");
        $("#chat-link").button("reset");
        $("#chat").html(response);
      });
    }
  }

  var prevLength = 0;

  function updateChat() {
    $.ajax({
        type: "POST",
        url: "/pages/updateChat",
        success: function(html){
            $("#chat").html(html);

            if($("#chat-container").length) {
              var chatSize = $("#chat-container").attr("chat_size");

              if(chatSize != prevLength) {
                chatDing.play();
              }

              prevLength = chatSize;
            }

            setTimeout(updateChat, 5000);
        }
    });
  }

  function init() {
    if(isPlayer1 && gameObject.Game.player2 == "") {
      $("#game-url").focus(function() { $(this).select(); } );

      startCondition = setInterval(checkStartCondition, 1000);

      // if no player 2 yet, show prompt
      $("#newGameModal").modal("show"); 

      initGame();
    }
    else {
      // draw game boad and setup current state
      initGame();

      updateChat();
    }
  }

  function checkStartCondition() {
    if(!inProcess) {
      inProcess = true;

      $.getJSON("/pages/checkStartCondition/" + gameObject.Game.game_key, function(response) {
        if(response.result == "SUCCESS") {
          inProcess = false;
          clearInterval(startCondition);

          $("#newGameModal").modal("hide"); 

          alert(response.name + " has joined your game. You play first.<br/><br/>Ready Player 1?", "Yes");

          gameObject.Game.player2 = response.name;

          // game is starting
          initGame();

          updateChat();
          setTimeout(updateChat, 5000);
        }
        else {
          inProcess = false;
        }
      });
    }
  }

  function alert(message, btnLabel) {
    if(btnLabel == undefined) btnLabel = "OK";

    bootbox.dialog({
      message: message,
      buttons: {
        success: {
          label: btnLabel,
          className: "btn",
          callback: function() {
            bootbox.hideAll();
          }
        }
      }
    });
  }
</script>

<div id="newGameModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="modalContent">
          <div class="modal-body">
            <h2 style="text-align: center;">Welcome to Monorail</h2>

            <p style="text-align: center; margin-top: 40px;">To get started, you need a second player.</p>

            <p style="text-align: center;">Copy the URL below and send it to who you wish to play. </p>

            <p style="text-align: center;">Once they arrive, your game will begin.</p>

            <hr />

            <div class="row">
              <div class="col-md-8 col-md-offset-2">
                <input id="game-url" type="text" style="width: 100%;" value="<?= _APP_URL?>/pages/play/<?= $game['Game']['game_key']; ?>" />
              </div>
            </div>
          </div>
        </div>
    </div>
</div> 

<div id="monorail-game" style="margin: 0 auto; width: 950px;">
  <h1 style="text-align: center;"><img width="350" src="/img/monorail.png" title="Monorail" /></h1>
  <div class="game-canvas">
    <div id="overlay2" style="display: none;" class="gradient overlay">
      <p id="player-info">Waiting for Player 2</p>
    </div>

    <div id="how-to-play" class="gradient">
      <div class="rules">
        <p class="title">How to Play</p>

        <p>Each turn, place between 1 and 3 tiles. Tiles must be in a straight line, must be connected to each other, and at least one of the placed tiles must be connected to an existing game tile.</p>

        <p>
          The first player to complete a single loop of the track wins.
        </p>

        <p>
          If you determine that a loop cannot be completed with the remaining tiles, you may declare the game impossible. Your opponent will have an opportunity to complete a loop with all remaining tiles. If they do, they win, if they cannot, you win.
        </p>

        <p>
          To add a tile to the board, click one of the two tiles on the right. You can then move that tile as you please.
        </p>

        <p>
          <div class="key">D</div>
          <div class="key-explanation">Removes selected tile.</div>

          <div class="clearfix">&nbsp;</div>

          <div class="key">R</div>
          <div class="key-explanation">Rotates selected tile.</div>

          <div class="clearfix">&nbsp;</div>
        </p>

        <p style="text-align: center;">
          <a href="#" id="close-how-to-btn">Hide How To</a>
        </p>
      </div>
    </div>

    <canvas id="canvas" width="600px" height="600px" style="background: #fff; margin:20px;">
    </canvas>

    <div class="canvas-controls" style="display: none;">
      <div class="control-container">
        <div><a title="Zoom In" href="#" id="zoom-in">+</a></div>

        <div class="line-divider">&nbsp;</div>

        <div>
          <a title="Zoom Out" href="#" id="zoom-out">-</a>
        </div>
      </div>
    </div>
  </div>

  <div class="game-options">
    <div id="overlay" class="gradient disable-settings">
    </div>

    <div id="pieces-left" class="tile-header">16 Tiles Left</div>

    <div class="tile-pieces">
      <div class="tile-container">
        <div class="straight-line">
          <a href="#" id="new-straight"><img src="/img/straight_tile.png" width="70" /></a>
        </div>
        <div class="curve">
          <a href="#" id="new-curve"><img src="/img/curve_tile.png" width="70" /></a>
        </div>
        <div class="clearfix">&nbsp;</div>
      </div>

      <p>Click Tile to Add</p>
    </div>

    <div id="players-turn"></div>

    <div class="btn-container">
      <a href="#" class="game-btn" style="width: 285px;" id="turn-complete"><span>Turn Complete</span></a>
    </div>

    <div class="btn-container">
      <a href="#" class="game-btn" style="width: 285px;" id="impossible"><span>Declare Impossible</span></a>

      <a href="#" class="game-btn" style="width: 285px; display: none;" id="yield-win"><span>Concede Win</span></a>
    </div>

    <div class="extra-btn-container">
      <div><a href="#" class="game-btn" style="width: 285px;" id="how-to-play-btn"><span>How to Play</span></a></div>
      <a href="#" class="game-btn" style="width: 285px; margin-top: 20px;" id="quit-game"><span>Quit Game</span></a>
      <div style="text-align: center; margin-top: 20px;">
        <a href="#" style="color: red; font-size: 10px;" id="join-game">Show Invite Code</a>
      </div>
    </div>
  </div>

  <div style="clear: both; height: 1px;">&nbsp;</div>

  <div>
    <h3 style="color: #fff">Chat</h3>

    <div>
      <form id="chat-form" method="post">
        <input type="text" name="data[chat_text]" id="chat-text" style="height: 30px; margin-right: 10px; width: 800px; font-size: 14px; line-height: 27px;"><a href="#" data-loading-text="Saving" class="btn btn-default" id="chat-link">Send</a>
      </form>
    </div>  
    <div id="chat" style="margin-top: 10px;"></div>
  </div>
</div>
