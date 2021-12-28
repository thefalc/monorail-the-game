<? if($games): ?>
	<? foreach($games as $game): ?>
		<div style="margin: 0px auto 10px; width: 370px;">
			<div class="pull-left" style="width: 250px; text-align: left;">
		     	<?= $game['Game']['player1'] ?>
		     	<p style="font-size: 10px;">Started <?= formatRelativeDate($game['Game']['created_date']); ?></p>
		    </div>
		    <div class="pull-left" style="margin-right: 20px;">
				<a class="btn btn-default" href="/pages/join/<?= $game['Game']['game_key'] ?>" title="Join <?= $game['Game']['player1'] ?>">Join</a>
			</div>
		    <div class="clearfix"></div>
		</div>
	<? endforeach; ?>
	<div class="clearfix"></div>
<? else: ?>
	<p style="text-align: center;">No public games currently available</p>
<? endif; ?>