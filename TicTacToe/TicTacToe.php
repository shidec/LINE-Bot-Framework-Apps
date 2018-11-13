<?php
	class TicTacToe extends Line_Apps{

		var $data;
		var $imageUrl = 'https://yourdomain/image.php';
				
		function on_follow(){
			$messages = array("Welcome {$this->profile->display_name}.",
							  $this->createMenu());
			return $messages;
		}
		
		function on_message($message){
			$text = $message['text'];
			$messages = false;
			$db = new Database();
			$r = $db->query("SELECT * FROM matches WHERE (player_1 = '{$this->profile->user_id}' OR player_2 = '{$this->profile->user_id}') AND status = 'IN_GAME'");
			if(!$r->num_rows){
				if(strtolower($text) == '/play'){
					$r = $db->query("SELECT * FROM matches WHERE status = 'WAITING' AND player_1 <> '{$this->profile->user_id}'");
					if($r->num_rows){
						$dt = $r->fetch_object();
						$this->data = unserialize($dt->data);
						$db->query("UPDATE matches SET player_2 = '{$this->profile->user_id}', status = 'IN_GAME' WHERE id = $dt->id");
						$messages = array($this->createTemplate(true), "It's your turn.");
					}else{
						$fields = array(array(0, 0, 0), array(0, 0, 0), array(0, 0, 0));
						$data = serialize(array('fields' => $fields, 'currentPlayer' => 2));
						$db->query("INSERT INTO matches (player_1, status, data) VALUES ('{$this->profile->user_id}', 'WAITING', '$data')");
						$messages = 'Waiting for opponent';
					}
				}
			}else{
				$dt = $r->fetch_object();
				$this->data = unserialize($dt->data);
				$playerIndex = $this->profile->user_id == $dt->player_1 ? 1 : 2;
				if($this->data['currentPlayer'] == $playerIndex){
			        $pos = explode(',', $text);
			        if(count($pos) == 2 && is_numeric($pos[0]) && is_numeric($pos[1])){
			        	if($this->playIt((int) $pos[0], (int) $pos[1], $playerIndex)){
							$opponentId = $this->profile->user_id == $dt->player_1 ? $dt->player_2 : $dt->player_1; 
							$isCompleted = $this->checkCompleted($playerIndex);
							if($isCompleted){
								$messages = array();
								if($this->data['winner'] == 0){									
									$messages[] = $this->createTemplate(false);
									$messages[] = array('type' => 'text', 'text' => "It's a draw.");
									$messages[] = array('type' => 'sticker', 'packageId' => '2', 'stickerId' => '28');
									$messages[] = $this->createMenu();

									$this->bot->pushMessage($opponentId, $messages);
								}else{
									$messages[] = $this->createTemplate(false);
									$messages[] = array('type' => 'text', 'text' => "Hooray! You win.");
									$messages[] = array('type' => 'sticker', 'packageId' => '1', 'stickerId' => '114');
									$oppMessages[] = $this->createMenu();

									$oppMessages = array();
									$oppMessages[] = $this->createTemplate(false);
									$oppMessages[] = array('type' => 'text', 'text' => "Sorry, you lose..");
									$oppMessages[] = array('type' => 'sticker', 'packageId' => '1', 'stickerId' => '9');
									$oppMessages[] = $this->createMenu();
									$this->bot->pushMessage($opponentId, $oppMessages);
								}
								$data = serialize($this->data);
								$db->query("UPDATE matches SET data = '{$data}', status = 'COMPLETED' WHERE id = '{$dt->id}'");

						    }else{$this->data['currentPlayer'] = 3 - $playerIndex;
								$data = serialize($this->data);
								$db->query("UPDATE matches SET data = '{$data}' WHERE id = '{$dt->id}'");
				        		$messages = array($this->createTemplate(false), array('type' => 'text', 'text' => "Waiting for opponent's movements"));
				        		//-- send messages to opponent
						        $this->bot->pushMessage($opponentId, array($this->createTemplate(true), array('type' => 'text', 'text' => "It's your turn.")));
						    }
			        	}else{
			        		$messages = 'Wrong move';
			        	}		        	
			        }
			    }else{
			    	$messages = "Waiting for the opponent's movements";
			    }
			}
			$db->close();
			return $messages;
		}

		function playIt($x, $y, $value){
			if($this->data['fields'][$x][$y] == 0){
				$this->data['fields'][$x][$y] = $value;
				return true;
			}else{
				return false;
			}
		}

		function checkCompleted($p){
			$result = true;
								
			for($x = 0; $x < 3; $x++){
				$allCheckedV = true;
				$allCheckedH = true;
				$allCheckedD1 = true;
				$allCheckedD2 = true;
				for($y = 0; $y < 3; $y++){
					$allCheckedV &= $this->data['fields'][$x][$y] == $p;
					$allCheckedH &= $this->data['fields'][$y][$x] == $p;
					$allCheckedD1 &= $this->data['fields'][$y][$y] == $p;
					$allCheckedD2 &= $this->data['fields'][$y][2 - $y] == $p;
				}
				if($allCheckedV || $allCheckedH || $allCheckedD1 && $allCheckedD2){
					$this->data['winner'] = $p;
					return true;
				}	
			}


			for($y = 0; $y < 3; $y++){
				for($x = 0; $x < 3; $x++){
					if($this->data['fields'][$x][$y] == 0){
						$result = false;
						break;
					}
				}
				if(!$result){
					break;
				}
			}

			if($result){
				$this->data['winner'] = 0;
				return true;
			}
		}

		function createMenu(){
			$actions = array();
			$actions[] = array(
									'type' => 'message',
									'label' => 'Play',
									'text' => '/play');

			return array(
						'type' => 'template',
						'altText' => 'Play the game',
						'template' => array(
							'type' => 'buttons',
							'text' => 'Menu',
							'actions' => $actions
						)
					);
		}

		function createTemplate($canPlay){
			$sfields = '';
			for($y = 0; $y < 3; $y++){
				for($x = 0; $x < 3; $x++){
					$sfields .= $this->data['fields'][$x][$y];
				}
			}

			$message = array(
			  "type" => "imagemap",
			  "baseUrl" => "{$this->imageUrl}/{$sfields}",
			  "altText" => "TicTacToe",
			  "baseSize"=>  array(
			      "height" => 1040,
			      "width" => 1040
			  ),
			  "actions" => $this->getImageMaps($canPlay)
		   );
		   
		   return $message;
		}

		function getImageMaps($canPlay){
			$result = array();
			
			if($canPlay){ 
				for($y = 0; $y < 3; $y++){
					for($x = 0; $x < 3; $x++){
						if($this->data['fields'][$x][$y] == 0){
							$result[] = array(
									"type" => "message",
									"text" => "{$x},{$y}" ,
									"area" => array(
										"x" => $x * 346,
										"y" => $y * 346,
										"width" => 346,
										"height" => 346
										)
								);
						}
					}
				}
			}

			return $result;
		}
	}