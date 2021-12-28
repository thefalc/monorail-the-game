<? $this->Html->css('animate', null, array("inline"=>false)); ?>
<? $this->Html->script('wow.min', false); ?>

<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=seanfalconer"></script>

<script type="text/javascript">
	$(document).ready(function() {
        new WOW().init();

        $("body").on("shown.bs.modal", ".modal", function () {
            $("input:text:visible:first", this).focus();
        });

        $("#new-game").click(function(e) {
        	e.preventDefault();

        	$("#newGameModal").modal("show");
        });

        $("#join-game").click(function(e) {
          e.preventDefault();

          $("#joinGameModal").modal("show");
        });

        $("#start-btn").click(function(e) {
        	e.preventDefault();

        	startGame();
        });

        $("#join-btn").click(function(e) {
          e.preventDefault();

          joinGame();
        });

        $("#notification-link").click(function(e) {
          e.preventDefault();

          $("#notificationModal").modal("show");
        });

        $("#submit-btn").click(function(e) {
          e.preventDefault();

          saveEmail();
        });

        updateGameList();

        setTimeout(updateGameList, 5000);
   	});

    function saveEmail() {
      if($.trim($("#email-entry").val())) {
        var email = encodeURIComponent($("#email-entry").val());

        $.getJSON("/pages/saveEmail/" + email, function(response) {
          if(response.result == "SUCCESS") {
            $("#notificationModal").modal("hide"); 

            bootbox.alert("Your email has been saved.");
          }
          else {
            bootbox.alert(response.message);
          }
        });
      }
    }

    function joinGame() {
      if($.trim($("#join-code").val())) {
        window.location.href = "/pages/play/" + $.trim($("#join-code").val());
      }
    }

    function updateGameList() {
      $.ajax({
          type: "POST",
          url: "/pages/loadGames",
          success: function(html){
              $("#games").html(html);
          }
      });
    }

   	function startGame() {
   		if($.trim($("#player1-name").val())) {
   			var name = encodeURIComponent($("#player1-name").val());
        var email = encodeURIComponent($("#player1-email").val());
        var isPublic = 0;
        if($("#public-game").is(':checked')) {
          isPublic = 1;
        }

   			$.getJSON("/pages/startGame/" + name + "?p=" + isPublic + "&e=" + email, function(response) {
   				if(response.result == "SUCCESS") {
   					window.location.href = "/pages/play/" + response.game_key;
   				}
   				else {
   					bootbox.alert(response.message);
   				}
   			});
   		}
   		else {
   			bootbox.alert("Sorry, but you must supply a name in order to start a game.");
   		}
   	}
</script>

<div id="newGameModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="modalContent">
			<div class="modal-body">
				<h2 style="text-align: center; font-size: 18px;">Enter Your Name</h2>

				<div class="row">
					<div class="col-md-6 col-md-offset-3">
						<input type="text" placeholder="John Doe" value="<?= $player_name ?>" id="player1-name" />
					</div>
        </div>

        <div class="row">
          <div style="margin-top: 20px; text-align: center;" class="col-md-10 col-md-offset-1">
            <input type="checkbox" id="public-game" />
            Make public so anyone can join?
          </div>
        </div>
        
        <hr/>

        <div class="row">
          <div style="margin-top: 20px; text-align: center;" class="col-md-10 col-md-offset-1">
            <p>Be notified when someone joins?</p>
            <input type="text" style="width: 410px;" value="<?= $email ?>" placeholder="email@example.com" id="player1-email" />
          </div>

					<div class="col-md-6 col-md-offset-3" style="margin-top: 40px; text-align: center;">
						<a href="#" id="start-btn" class="game-btn"><span>Start Game</span></a>
			    </div>
				</div>
			</div>
        </div>
    </div>
</div> 

<div id="joinGameModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="modalContent">
      <div class="modal-body">
        <h2 style="text-align: center; font-size: 18px;">Enter Your Game Code</h2>

        <div class="row">
          <div class="col-md-6 col-md-offset-3">
            <input type="text" id="join-code" />
          </div>

          <div class="col-md-6 col-md-offset-3" style="margin-top: 20px; text-align: center;">
            <a href="#" id="join-btn" class="game-btn"><span>Join Game</span></a>
          </div>
        </div>
      </div>
        </div>
    </div>
</div> 

<div id="notificationModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="modalContent">
      <div class="modal-body">
        <h2 style="text-align: center; font-size: 18px;">Enter Your Email</h2>

        <div class="row">
          <div class="col-md-6 col-md-offset-3">
            <input type="text" id="email-entry" />
          </div>
        </div>

        <p style="text-align: center; margin-top: 20px;">Save your email and you will be notified when new games get created so you can join.</p>

        <div class="row">
          <div class="col-md-6 col-md-offset-3" style="margin-top: 20px; text-align: center;">
            <a href="#" id="submit-btn" class="game-btn"><span>Submit</span></a>
          </div>
        </div>

      </div>
        </div>
    </div>
</div> 

<div id="home-page" class="container">
	<h1><img src="/img/welcome-title.png" title="Welcome to Monorail"></h1>

	<div class="row">
		<div class="col-md-8 col-md-offset-2">
			<div class="game-container">
				<div class="row form" >
					<div class="col-md-8 col-md-offset-2">
						<div class="btn-container wow bounceIn">
							<p><a id="new-game" class="game-btn" href="#"><span>Start New Game</span></a></p>
							<p style="margin-top: 40px;"><a class="game-btn" id="join-game" href="#"><span>Join Existing Game</span></a></p>
						</div>
					</div>
				</div>
			</div>
		</div>		
	</div>

  <div class="row" style="margin-bottom: 30px; margin-top: 20px;">
    <div class="col-md-10 col-md-offset-1" style="text-align: center;">
      <a href="#" style="color: red; font-size: 10px; text-decoration: underline;" id="notification-link">Receive notifications about new games?</a>

      <hr />

      <h3 style="text-align: center; color: #fff; font-size: 16px;">Games Available to Join</h3>

      <div id="games" style="color: #ddd; margin: 20px auto;">
        <p style="text-align: center">Loading...</p>
      </div>
    </div>
  </div>

  <div style="margin: 0 auto; width: 180px;" class="addthis_sharing_toolbox"></div>
</div>



      
