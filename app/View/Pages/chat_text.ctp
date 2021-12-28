<? if(isset($chats) && $chats): ?>
	<? $first_color = "#fff"; $second_color = "red"; ?>
	<? $first_name = $chats[count($chats)-1]['Chat']['player_name']; ?>

	<div id="chat-container" chat_size="<?= count($chats); ?>" style="padding: 10px; max-height: 130px; height:auto !important; height: 130px; overflow-y: auto; border: 1px solid #ccc">
		<? foreach($chats as $chat): ?>
			<? if($first_name == $chat['Chat']['player_name']): ?>
				<p style="font-size: 12px; color: <?= $first_color ?>; margin-bottom: 5px;"><?= $chat['Chat']['player_name']; ?> said <?= $chat['Chat']['chat_text']; ?> - <?= formatRelativeDate($chat['Chat']['created_date']); ?></p>
			<? else: ?>
				<p style="font-size: 12px; color: <?= $second_color ?>; margin-bottom: 5px;"><?= $chat['Chat']['player_name']; ?> said <?= $chat['Chat']['chat_text']; ?> - <?= formatRelativeDate($chat['Chat']['created_date']); ?></p>
			<? endif; ?>
		<? endforeach; ?>
	</div>
<? else: ?>
	<p style="color: yellow">No chat history</p>
<? endif; ?>