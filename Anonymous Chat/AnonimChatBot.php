<?php
	class AnonimChatBot extends Line_Apps{
				
		function on_follow(){
			$messages = array("Welcome {$this->profile->display_name}.",
							  "Just type anything, someone might reply.");
			return $messages;
		}
		
		function on_message($message){
			$messages = '';
			$db = new Database();
			$r = $db->query("SELECT * FROM chats WHERE (player_1 = '{$this->profile->user_id}' OR player_2 = '{$this->profile->user_id}') AND status = 'IN_GAME'");
			if(!$r->num_rows){
				$r = $db->query("SELECT id, player_1, data FROM chats WHERE status = 'WAITING'");
				if($r->num_rows){
					$dt = $r->fetch_object();
					if($dt->player_1 == $this->profile->user_id){
						$text = $db->escape(json_encode($message));
						$db->query("UPDATE chats SET data = '{$text}' WHERE id = '{$dt->id}'");
					}else{
						$db->query("UPDATE chats SET player_2 = '{$this->profile->user_id}', status = 'IN_GAME' WHERE id = '{$dt->id}'");
						if($this->bot) $this->bot->pushMessage($dt->player_1, array(json_decode($dt->data)));
						$messages = $dt->data;
					}
				}else{
					$text = $db->escape(json_encode($message));
					$db->query("INSERT INTO chats (player_1, status, data) VALUES ('{$this->profile->user_id}', 'WAITING', '{$text}')");
				}
			}else{
				$dt = $r->fetch_object();
		        if(strtolower($message['text']) == '/bye'){
		        	$db->query("UPDATE chats SET status = 'COMPLETED' WHERE id = $dt->id");
		        }
		        //--PLAY/EXCHANGE MESSAGES            
		        if($this->profile->user_id == $dt->player_1){
		            if($this->bot) $this->bot->pushMessage($dt->player_2, array($message));
		        }else{
		            if($this->bot) $this->bot->pushMessage($dt->player_1, array($message));
		        }
			}
			$db->close();
			return $messages;
		}
	}