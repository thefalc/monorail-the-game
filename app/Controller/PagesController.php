<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller p 
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppController', 'Controller');
App::uses('CakeEmail', 'Network/Email');

include_once SYS_DIR."Config/constants.php";

/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class PagesController extends AppController {
	var $helpers = array("Html", "Form", "Js");
    var $components = array("Session", "RequestHandler", "Cookie");

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();

	public function clearModels() {
        $this->autoRender = false;
        Cache::clear(false, '_cake_model_');
    }

    public function saveEmail($email) {
    	$this->autoRender = false;

    	if($email) {
    		$this->Email = ClassRegistry::init("Email");
    		if(!$this->Email->findByEmail($email)) {
    			$this->Email->create();

    			$this->Email->save(array("email" => $email, "last_contact_date" => date("Y-m-d H:i:s", strtotime("0000-00-00 00:00:00")), "created_date" => date("Y-m-d H:i:s")));
    		}
    		
    		$retval = array("result" => "SUCCESS");
    	}
    	else {
			$retval = array("result" => "FAILURE", "message" => "Sorry, could not save your email.");	
		}

		$this->response->body(json_encode($retval));
        $this->response->type('json'); 
    }

	public function loadGames() {
		$this->layout = 'ajax';

		$this->Game = ClassRegistry::init("Game");

		$date_cut_off = date("Y-m-d H:i:s", strtotime("-1 hours"));

		$games = $this->Game->find("all", array("conditions" => array("created_date >=" => $date_cut_off, "public" => 1, "player2" => "", "player1_quit" => 0)));
		$this->set("games", $games);
	}

	public function saveChat() {
		$this->layout = 'ajax';

		$this->Game = ClassRegistry::init("Game");
		$this->Chat = ClassRegistry::init("Chat");

		if($this->Session->check("game_key")) {
			$game_key = $this->Session->read("game_key");
			$game = $this->Game->findByGameKey($game_key, array("id"));

			$player_name = $this->Session->read("player_name");
			$chat_text = $this->data['chat_text'];

			if($chat_text) {
				$this->Chat->create();
				$this->Chat->save(array("game_id" => $game['Game']['id'], "player_name" => $player_name, "chat_text" => $chat_text, "created_date" => date("Y-m-d H:i:s")));
			}

			$chats = $this->Chat->find("all", array("conditions" => array("game_id" => $game['Game']['id']), "order" => array("created_date DESC")));
			$this->set("chats", $chats);
		}

		$this->render("chat_text");
	}

	public function updateChat() {
		$this->layout = 'ajax';

		$this->Game = ClassRegistry::init("Game");
		$this->Chat = ClassRegistry::init("Chat");

		if($this->Session->check("game_key")) {
			$game_key = $this->Session->read("game_key");
			$game = $this->Game->findByGameKey($game_key, array("id"));

			$player_name = $this->Session->read("player_name");

			$chats = $this->Chat->find("all", array("conditions" => array("game_id" => $game['Game']['id']), "order" => array("created_date DESC")));
			$this->set("chats", $chats);
		}

		$this->render("chat_text");
	}

	private function checkSession() {
		if($this->Session->check("game_key")) {
			$game_key = $this->Session->read("game_key");

			$this->redirect("/pages/play/".$game_key);
		}
	}

	function tempPlay() {
		$this->Game = ClassRegistry::init("Game");

		$game_key = "5B09STUZ3B";

		$game = $this->Game->findByGameKey($game_key);

		$this->set("game", $game);
		$this->set("is_player_1", 1);
	}

	public function startNewGame() {
		if($this->Session->check("is_player_1") && $this->Session->check("game_key")) { 
			$this->Game = ClassRegistry::init("Game");

			$old_game_key = $this->Session->read("game_key");

			$this->Game = ClassRegistry::init("Game");

			// look up the previous game and get the person's name
			$game = $this->Game->findByGameKey($old_game_key);
			$name = $game['Game']['player1'];

			$this->Session->destroy();

			// create a new game to start
			$this->Game->create();

			$game_key = $this->randString(10);
			if($this->Game->save(array("game_key" => $game_key, "player1" => $name, "created_date" => date("Y-m-d H:i:s")))) {
				$this->Session->write("is_player_1", true);
				$this->Session->write("player_name", $name);
				$this->Session->write("game_key", $game_key);

				$this->redirect("/pages/play/".$game_key);
			}
		}
		else {
			$this->Session->destroy();

			$this->redirect("/pages/home");
		}
	}

	public function impossible($game_key) {
		$this->autoRender = false; 

		$this->Game = ClassRegistry::init("Game");
		$game = $this->Game->findByGameKey($game_key);

		if($game) {
			if($this->Session->check("is_player_1")) {
				$game['Game']['declared_impossible'] = 1;
			}
			else {
				$game['Game']['declared_impossible'] = 2;
			}

			$game['Game']['last_player'] = !$game['Game']['last_player'];
			
			$this->Game->save($game);

			$retval = array("result" => "SUCCESS", "game" => $game);
		}
		else {
			$retval = array("result" => "FAILURE", "message" => "Error updating the game state.");	
		}

		$this->response->body(json_encode($retval));
        $this->response->type('json'); 
	}

	public function quit($game_key) {
		$this->autoRender = false; 

		$this->Game = ClassRegistry::init("Game");
		$game = $this->Game->findByGameKey($game_key);

		if($game) {
			if($this->Session->check("is_player_1")) {
				$game['Game']['player1_quit'] = 1;
			}
			else {
				$game['Game']['player2_quit'] = 1;
			}

			$this->Game->save($game);
			
			$this->Session->destroy();

			$retval = array("result" => "SUCCESS");
		}
		else {
			$this->Session->destroy();
			
			$retval = array("result" => "FAILURE", "message" => "Error updating the game state.");	
		}

		$this->response->body(json_encode($retval));
        $this->response->type('json'); 
	}

	/**
	*	If the game has been declared impossible and the other player cannot complete the board
	*	they can concede the win to the player that declared the board impossible.
	*/	
	public function concede($game_key) {
		$this->autoRender = false;

		$this->Game = ClassRegistry::init("Game");
		$game = $this->Game->findByGameKey($game_key);

		if($game) {
			if($this->Session->check("is_player_1")) {
				$game['Game']['winner'] = 2;
			}
			else {
				$game['Game']['winner'] = 1;
			}

			$this->Game->save($game);
	
			$retval = array("result" => "SUCCESS", "game" => $game);
		}
		else {
			$retval = array("result" => "FAILURE", "message" => "Error updating the game state.");	
		}

		$this->response->body(json_encode($retval));
        $this->response->type('json'); 
	}

	public function save($game_key, $tiles_used, $win = false) {
		$this->autoRender = false;

		$tiles = $_POST['GameBoard'];

		$this->Game = ClassRegistry::init("Game");
		$game = $this->Game->findByGameKey($game_key);

		if($win) {
			if($this->Session->check("is_player_1")) {
				$game['Game']['winner'] = 1;
			}
			else {
				$game['Game']['winner'] = 2;
			}
		}

		$game['Game']['tiles_used'] += $tiles_used;
		$game['Game']['board'] = $tiles;
		$game['Game']['last_player'] = !$game['Game']['last_player'];

		if($this->Game->save($game)) {
			$retval = array("result" => "SUCCESS", "game" => $game);
		}
		else {
			$retval = array("result" => "FAILURE", "message" => "Error updating the game state.");	
		}

		$this->response->body(json_encode($retval));
        $this->response->type('json'); 
	}

	public function home() {
		$this->checkSession();

		$player_name = "";
		$email = "";
		if($this->Cookie->check("player_name")) {
            $player_name = $this->Cookie->read("player_name");    
        }

        if($this->Cookie->check("email")) {
            $email = $this->Cookie->read("email");    
        }

		$this->set("player_name", $player_name);
		$this->set("email", $email);

		$this->set("title_for_layout", "Play The Genius Monorail Game");
	}

	public function update($game_key) {
		$this->autoRender = false;

		$this->Game = ClassRegistry::init("Game");

		$game = $this->Game->findByGameKey($game_key);
		if($game) {
			$retval = array("result" => "SUCCESS", "game" => $game);
		}
		else {
			$retval = array("result" => "FAILURE");
		}

		$this->response->body(json_encode($retval));
        $this->response->type('json');
	}

	public function checkStartCondition($game_key) {
		$this->autoRender = false;

		$this->Game = ClassRegistry::init("Game");

		$game = $this->Game->findByGameKey($game_key);
		if($game['Game']['player2']) {
			$retval = array("result" => "SUCCESS", "game" => $game, "name" => $game['Game']['player2']);
		}
		else {
			$retval = array("result" => "FAILURE");
		}

		$this->response->body(json_encode($retval));
        $this->response->type('json');
	}

	public function play($game_key) {
		$this->Game = ClassRegistry::init("Game");

		$game = $this->Game->findByGameKey($game_key);

		if($game) {
			$this->set("game", $game);

			// is this player 1?
			if($this->Session->check("is_player_1")) {
				$this->set("is_player_1", 1);
				$this->set("title_for_layout", "Play The Genius Monorail Game as ".$game['Game']['player1']);
			}
			else {
				$this->set("is_player_1", 0);
				// check if we have a player 2 yet
				if($game['Game']['player2'] && $this->Session->check("is_player_2")) {
					$this->set("title_for_layout", "Play The Genius Monorail Game as ".$game['Game']['player2']);
				}
				elseif(!$game['Game']['player2']) { // need to get this player to join
					$this->redirect("/pages/join/".$game_key);
				}
				else {
					$this->Session->setFlash("Sorry, but this game already has two players.");
					$this->redirect("/");
				}
			}
		}
		else {
			$this->Session->setFlash("Sorry, but your game could not be located. Please try starting again.");
			$this->redirect("/");
		}
	}

	public function join($game_key) {
		$this->Game = ClassRegistry::init("Game");

		// check to see if this person is already part of another game
		if($this->Session->check("game_key")) {
			$session_game_key = $this->Session->read("game_key");
			// part of another game
			if($session_game_key != $game_key) {
				$game = $this->Game->findByGameKey($session_game_key);

				// if game is done
				if($game['Game']['winner'] > 0) {
					$this->Session->destroy();
				}
			}
		}

		$this->checkSession();

		$game = $this->Game->findByGameKey($game_key);

		// existing player?
		if($this->Session->check("is_player_1") || $this->Session->check("is_player_2")) {
			$this->redirect("/pages/play/".$game_key);
		}
		else {
			// if no player 2, give this person a chance to join
			if(!$game['Game']['player2']) {
				$this->set("title_for_layout", "Join Monorail match with ".$game['Game']['player1']);

				$this->set("game", $game);
			}
			else { // game is already full
				$this->Session->setFlash("Sorry, but this game already has two players.");
				$this->redirect("/");
			}
		}

		if($game) {
			$this->set("game", $game);
		}
		else {
			$this->Session->setFlash("Sorry, but your game could not be located. Please try starting again.");
			$this->redirect("/");
		}

		$player_name = "";
		$email = "";
		if($this->Cookie->check("player_name")) {
            $player_name = $this->Cookie->read("player_name");    
        }

        $this->set("player_name", $player_name);
	}

	public function startGame($name, $game_key = "") {
		$this->autoRender = false; 

		$this->Game = ClassRegistry::init("Game");

		// creating a game from scratch
		if(!$game_key) {
			$this->Game->create();

			$game_key = $this->randString(10);

			if(isset($this->request->query['p'])) {
				$is_public = $this->request->query['p'];	
			}
			else {
				$is_public = false;
			}

			if(isset($this->request->query['e'])) {
				$email = $this->request->query['e'];	
			}
			else {
				$email = "";
			}

			if($this->Game->save(array("game_key" => $game_key, "email" => $email, "public" => $is_public, "player1" => $name, "created_date" => date("Y-m-d H:i:s")))) {
				$retval = array("result" => "SUCCESS", "game_key" => $game_key);

				$this->Session->write("is_player_1", true);
				$this->Session->write("player_name", $name);
				$this->Session->write("game_key", $game_key);

				$this->Cookie->write("player_name", $name, false, "+4 weeks");
				$this->Cookie->write("email", $email, false, "+4 weeks");
			}
			else {
				$retval = array("result" => "FAILURE", "message" => "Sorry, could not create a new game.");
			}
		}
		else { // joining an existing game as player 2
			$game = $this->Game->findByGameKey($game_key);

			// make sure the game exists
			if($game) {
				if(!$game['Game']['player2']) {
					$game['Game']['player2'] = $name;

					$this->Game->save($game);

					$this->Session->write("is_player_2", true);
					$this->Session->write("player_name", $name);
					$this->Session->write("game_key", $game_key);

					$this->Cookie->write("player_name", $name, false, "+4 weeks");

					if($game['Game']['email']) {
						$this->notifiyPlayer1($game['Game']['email'], $name, $game_key);
					}

					$retval = array("result" => "SUCCESS", "game_key" => $game_key);
				}
				else {
					$retval = array("result" => "FAILURE", "message" => "Sorry, looks like this game already has two players.");
				}
			}
			else {
				$retval = array("result" => "FAILURE", "message" => "Sorry, could not find the game.");
			}
		}
		
		$this->response->body(json_encode($retval));
        $this->response->type('json');
	}

	private function randString($len, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
        $string = '';
        for ($i = 0; $i < $len; $i++) {
            $pos = rand(0, strlen($chars)-1);
            $string .= $chars{$pos};
        }
        return $string;
    }

    public function notifiyPlayer1($email, $name, $game_key) {
    	$Email = new CakeEmail('default');
        $Email->template('player_joined_notification');
        $Email->viewVars(array("game_key" => $game_key, "name" => $name));
        $Email->from(array('falconer.sean@gmail.com' => 'Sean Falconer'));
        $Email->to($email);
        $Email->subject("[MONORAIL] ". $name. " joined your game");
        $Email->emailFormat('html');
        $Email->send();
    }
}
