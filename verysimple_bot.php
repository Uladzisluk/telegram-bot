<?php

	// определим кодировку UTF-8
	header('Content-type: text/html; charset=utf-8');
	
	$bot = new Bot();
	
	// передаем методу init() данные от BOT API
	$bot->init('php://input');
	
	class Bot{
		
		// токен API
		private $botToken = "5247683525:AAERZIA-R2TWaPUMmBX7bY2_Jq9NRrzfeGE";
		
		// мой ID Telegram
		private $adminId = 393903185;
		
		private $helloAdmin = "Начинаем ждать сообщений от пользователей.";
		
		private $helloUser = "Приветствую Вас {username} . \nЯ очень жду вашего сообщения.\n------\nСпасибо.";
		
		public function init($data){
			
			// создаем массив из пришедших данных от API Telegram
			$arrData = $this->getData($data);
			
			// определяем id отправителя
			$chat_id = $arrData['message']['chat']['id'];
	
			$this->checkVar($arrData);
			
			// если это Старт
			if($this->isStartBot($arrData)){
				
				//определяем id чата куда будем отправять админу или пользователю
				$chat_id = $this->isAdmin($chat_id) ? $this->adminId : $chat_id;
				
				// выводим приветствие
				$hello = $this->isAdmin($chat_id) ? $this->helloAdmin : $this->setTextHello($this->helloUser, $arrData);
				
				// отправляем приветствие
				$this->requestToTelegram(array("text" => $hello), $chat_id, "sendMessage");
				
			}else{
				// если это не Старт
				if($this->isAdmin($chat_id)) {
							 // здесь далее будет код если сообщение отправляет админ
							if($this->isReply($arrData)){
								
								// если ответ самому себе
								if($this->isAdmin($arrData['message']['reply_to_message']['from']['id'])) {
									$this->requestToTelegram(array("text"=>"Вы ответили на свое сообщение."), $this->adminId, "sendMessage");
								} elseif($this->isBot($arrData)){
									
									// если ответ боту
									$this->requestToTelegram(array("text"=>"Вы ответили на сообщение бота."), $this->adminId, "sendMessage");
								} else {
									
									// все нормально, отправляем на обработку
									$this->getTypeCommand($arrData);
								}
							} else{
								$this->requestToTelegram(array("text"=> "Ответьте на сообщение"), $this->adminId, "sendMessage");
							}
				} else{
					
					// Если код написал пользователь, то перенаправляем админу
					$dataSend = array('from_chat_id' => $chat_id, 'message_id' => $arrData['message']['message_id']);
					$this->requestToTelegram($dataSend, $this->adminId, "forwardMessage");
				}
				
			}
		}
		
		
		
		private function isReply($data){
			return array_key_exists('reply_to_message', $data['message']) ? true : false;
		}
		
		private function isBot($data){
			return ($data['message']['reply_to_message']['from']['is_bot'] == 1 && !array_key_exists('forward_from', $data['message']['reply_to_message']));
		}
		
		
		
		private function checkVar($var){
			$fh = fopen('check.txt', 'w');
			fwrite($fh, print_r($var, TRUE)."\n");
			fclose($fh);
		}
		
		private function setTextHello($text, $data){
			
			//узнаем имя и фамилию отправителя
			$username = $this->getNameUser($data);
			
			//подменяем {username} на Имя и Фамилию
			return str_replace("{username}", $username, $text);
		}
		
		// получаем name и surname пользователя
		private function getNameUser($data){
			return $data['message']['chat']['first_name'] . " " . $data['message']['chat']['last_name'];
		}
		
		
		// преобразовываем входящие данные в массив
		private function getData($data){
			return json_decode(file_get_contents($data), TRUE);
		}
		
		
		// создаем файд отладки
		private function setFileLog($data){
			$fh = fopen('log.txt', 'a') or die('can\'t open file');
			((is_array($data)) || (is_object($data))) ? fwrite($fh, print_r($data, TRUE)."\n") : fwrite($fh, $data . "\n");
			fclose($fh);
		}
		
		
		//проверим кто пишет админ или нет
		private function isAdmin($id) {
			return ($id == $this->adminId) ? true : false;
		}
		
		
		// проверим на начало диалога с ботом
		private function isStartBot($data){
			return ($data['message']['text'] == "/start") ? true : false;
		}
		
		private function getTypeCommand($data){
			
			// определяем id пользователя для уведомления
			$chat_id = $data['message']['reply_to_message']['forward_from']['id'];
			
			// если текст
			if(array_key_exists('text', $data['message'])){
				
				// готовим данные
				$dataSend = array(
					'text' => $data['message']['text'],
				);
				
				// отправялем - передаем нужный метод
				$this->requestToTelegram($dataSend, $chat_id, "sendMessage");
			} elseif (array_key_exists('document', $data['message'])) {
				$dataSend = array(
					'document' => $data['message']['document']['file_id'],
					'caption' => $data['message']['caption'],
				);
				$this->requestToTelegram($dataSend, $chat_id, "sendDocument");
			}elseif (array_key_exists('photo', $data['message'])) {
				// картинки Телеграм ресайзит и предлагает разные размеры
				// мы берем самый последний вариант
				// так как он самый больщой - то есть оригинал
				$img_num = count($data['message']['photo'])-1;
				$dataSend = array(
					'photo' => $data['message']['photo'][$img_num]['file_id'],
					'caption' => $data['message']['caption'],
				);
				$this->requestToTelegram($dataSend, $chat_id, "sendPhoto");
			}elseif(array_key_exists('video', $data['message'])){
				$dataSend = array(
					'video' => $data['message']['video']['file_id'],
					'caption' => $data['message']['caption'],
				);
				$this->requestToTelegram($dataSend, $chat_id, "sendVideo");
			}else{
				// другие тип не поддерживаем
				$this->requestToTelegram(array("text" => "Тип передаваемого сообщения не поддерживается"), $chat_id, "sendMessage");
			}
		}
		
		
		// передаем массив данных, id чата, и метод передачи данных
		private function requestToTelegram($data, $chat_id, $type){
			$result = null;
			
			// id чата куда отправляем
			$data['chat_id'] = $chat_id;

			if(is_array($data)){
				
					// иницилизируем curl
					$ch = curl_init();
					
					// укажем url доставки запроса
					curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot".$this->botToken.'/'.$type);
					
					// скажем что хотимм отправить POST запрос 
					curl_setopt($ch, CURLOPT_POST, count($data));
					
					// генерируем URL-кодированную строку запроса
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
					
					//выполним curl
					$result = curl_exec($ch);
					
					//закрываем CURL
					curl_close($ch);
					
			}
			
			
			
		}
	}