<?php

	require_once "connect.php";
	
	$connection = @new mysqli($db_host, $db_username, $db_password, $db_database);
	
	if($connection->connect_errno!=0){
		echo "Error: ".$connection->connect_errno." Opis: ".$connection->connect_error;
	}else{
		echo "Db is connected";
		$sql = "SELECT * FROM channels WHERE channel='ТОПЛЕС'";
		
		if($result = @$connection->query($sql))
		{
			$nr_channels = $result->num_rows;
			if($nr_channels>0){
				$line = $result->fetch_assoc();
				$tg = $line['tg'];
				
				$result->close();
				
				echo $tg;
			}else{
				
			}
		}
		
		$connection->close();
	}

	header('Content-Type: text/html; charset=utf-8'); // на всякий случай досообщим PHP, что все в кодировке UTF-8

	$site_dir = dirname(dirname(__FILE__)).'/'; // корень сайта
	$bot_token = '5247683525:AAERZIA-R2TWaPUMmBX7bY2_Jq9NRrzfeGE'; // токен вашего бота
	$data = file_get_contents('php://input'); // весь ввод перенаправляем в $data
	$data = json_decode($data, true); // декодируем json-закодированные-текстовые данные в PHP-массив

	// Для отладки, добавим запись полученных декодированных данных в файл message.txt, 
	// который можно смотреть и понимать, что происходит при запросе к боту
	// Позже, когда все будет работать закомментируйте эту строку:
	file_put_contents(__DIR__ . '/message.txt', print_r($data, true));

	// Основной код: получаем сообщение, что юзер отправил боту и 
	// заполняем переменные для дальнейшего использования
	if (!empty($data['message']['text'])) {
		$chat_id = $data['message']['from']['id'];
		$user_name = $data['message']['from']['username'];
		$first_name = $data['message']['from']['first_name'];
		$last_name = $data['message']['from']['last_name'];
		$text = trim($data['message']['text']);
		$text_array = explode(" ", $text);
		
		switch($text){
			case '/start':{
				$text_return = "Привет, $first_name $last_name!!
Я бот, который поможет тебе найти социальную сеть
любого блогера! Попробуй!!";
				break;
			}
			case '/help':{
				$text_return = "Команды, которые я понимаю:
/help - список команд
/about - о нас";
				break;
			}
			case '/about':{
				$text_return = "Я beta-версия channel search бота,
который поможет найти тебе
социальную сеть любого блогера!";
				break;
			}
		}
		message_to_telegram($bot_token, $chat_id, $text_return);
	}

	// функция отправки сообщени в от бота в диалог с юзером
	function message_to_telegram($bot_token, $chat_id, $text, $reply_markup = '')
	{
		$ch = curl_init();
		$ch_post = [
			CURLOPT_URL => 'https://api.telegram.org/bot' . $bot_token . '/sendMessage',
			CURLOPT_POST => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_POSTFIELDS => [
				'chat_id' => $chat_id,
				'parse_mode' => 'HTML',
				'text' => $text,
				'reply_markup' => $reply_markup,
			]
		];

		curl_setopt_array($ch, $ch_post);
		curl_exec($ch);
	}