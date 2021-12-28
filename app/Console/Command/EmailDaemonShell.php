<?php

App::uses('Shell', 'Console');
App::uses('CakeEmail', 'Network/Email');

class EmailDaemonShell extends Shell {
	public function main() {
		$this->Email = ClassRegistry::init("Email");
		$this->Game = ClassRegistry::init("Game");

		$date_cut_off = date("Y-m-d H:i:s", strtotime("-20 minutes"));

		$games = $this->Game->find("all", array("conditions" => array("created_date >=" => $date_cut_off, "public" => 1, "player2" => "", "player1_quit" => 0)));
		
		if($games) {
			$emails = $this->Email->find("all", array("conditions" => array("last_sent_date <" => $date_cut_off)));

			foreach($emails as &$email) {
				if($this->sendNotification($email, $games)) {
					$email['Email']['last_sent_date'] = date("Y-m-d H:i:s");	
				}
			}

			$this->Email->saveAll($emails);
		}
	}

	private function sendNotification($email, $games) {
		$total_games = array();
		$count = 0;
		foreach($games as $game) {
			if($game['Game']['email'] != $email['Email']['email']) {
				$total_games[] = $game;
			}
		}

		if($total_games) {
			$Email = new CakeEmail('default');
	        $Email->template('game_started_notification');
	        $Email->viewVars(array("games" => $total_games));
	        $Email->from(array('falconer.sean@gmail.com' => 'Sean Falconer'));
	        $Email->to($email['Email']['email']);
	        $Email->subject("[MONORAIL] - New Games Available");
	        $Email->emailFormat('html');
	        $Email->send();

	        return true;
		}		

		return false;
	}
}
