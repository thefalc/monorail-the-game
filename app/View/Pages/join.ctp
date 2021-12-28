<? $this->Html->css('animate', null, array("inline"=>false)); ?>
<? $this->Html->script('wow.min', false); ?>

<script type="text/javascript">
  var gameKey = "<?= $game['Game']['game_key'] ?>";
	$(document).ready(function() {
        new WOW().init();

        $("#join-link").click(function(e) {
        	e.preventDefault();

        	startGame();
        });
   	});

   	function startGame() {
   		if($.trim($("#player2-name").val())) {
   			var name = encodeURIComponent($("#player2-name").val());
        $("#join-link").button("loading");
   			$.getJSON("/pages/startGame/" + name + "/" + gameKey, function(response) {
   				if(response.result == "SUCCESS") {
   					window.location.href = "/pages/play/" + response.game_key;
   				}
   				else {
            $("#join-link").button("reset");
   					bootbox.alert(response.message);
   				}
   			});
   		}
   		else {
   			bootbox.alert("Sorry, but you must supply a name in order to join this game.");
   		}
   	}
</script>

<div id="home-page" class="container">
	<h1><img src="/css/join-game.png" title="Join Game"></h1>

	<div class="row">
		<div class="col-md-8 col-md-offset-2">
			<div class="game-container">
				<div class="row form" >
					<div class="col-md-8 col-md-offset-2">
						<div class="btn-container wow bounceIn" style="text-align: center;">
							<input type="text" value="<?= $player_name ?>" placeholder="Enter your name" id="player2-name" style="font-size: 20px; line-height: 30px; width: 300px;" />
              <div style="margin-top: 20px;">
                <a href="#" data-loading-text="Joining..." id="join-link" class="game-btn"><span>Join Game</span></a>
              </div>
						</div>
					</div>
				</div>
			</div>
		</div>		
	</div>
</div>



      
