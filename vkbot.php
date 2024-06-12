<?php

if (!isset($_REQUEST)) {
	return;
}

//Строка для подтверждения адреса сервера из настроек Callback API
$confirmation_token = file_get_contents(__DIR__."/init/confirm_token.txt");

//Ключ доступа сообщества
$token = file_get_contents(__DIR__."/init/confirm_token.txt");

//Получаем и декодируем уведомление
$data = json_decode(file_get_contents('php://input'));

//Проверяем, что находится в поле "type"
switch ($data->type) {
	//Если это уведомление для подтверждения адреса...
	case 'confirmation':
		//...отправляем строку для подтверждения
		echo $confirmation_token;
		break;

	//Если это уведомление о новом сообщении...
	case 'message_new':
		//...получаем id его автора
		$user_id = $data->object->message->from_id;
		//затем с помощью users.get получаем данные об авторе
		$user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&access_token={$token}&v=5.103"));

		//и извлекаем из ответа его имя
		$user_name = $user_info->response[0]->first_name;
		//ответ юзера
		$user_response = $data->object->message->text;
		//прикрепление по умолчанию
		$attachment = "";

		//ответное сообщение
		if ($user_response == "Начать") {
			$resp_message = "Привет, {$user_name}! Я Дэвид Моррелл. Можешь спросить меня о моих книгах, чем я занимаюсь в данный момент, посоветовать книгу, аудиокнигу и многое другое!";
		} elseif (preg_match("/([Пп]ривет.*) | ([Зз]дравству).+\sкниг.+/i", $user_response)) {
			$resp_message = "Привет, {$user_name}! Я Дэвид Моррелл. Можешь спросить меня о моих книгах, чем я занимаюсь в данный момент, посоветовать книгу, аудиокнигу и многое другое!";
		} elseif (preg_match("/[Пп]осовет.+\sкниг.+/i", $user_response)) {
			$resp_message = "Советую прочитать мою книгу Братство Розы!";
			$attachment = "photo-66515599_336335563";
		} elseif (preg_match("/[Пп]осовет.+\sаудиокниг.+/i", $user_response)) {
			$resp_message = "Советую прослушать мою аудиокнигу Рэмбо: Первая кровь:";
			$attachment = "audio_playlist-66515599_49233792";
		} elseif (preg_match("/([Оо] чём.+)|([Оо] чем.+)/i", $user_response)) {
			$resp_message = "Эта книга о страхе быть одиноким в обществе людей.";
		} elseif (preg_match("/[Чч]ем .+/i", $user_response)) {
			$resp_message = "Сейчас я пишу рассказы. Недавно издал сборник!";
		} elseif (preg_match("/([Пп]ока.*)|([Дд]о свидан.+)/i", $user_response)) {
			$resp_message = "Всего хорошего! Заходи ещё.";
		} else {
			$resp_message = "Я тебя не понимаю! Пожалуйста, переформулируй своё предложение. Можешь спросить меня о моих книгах, чем я занимаюсь в данный момент, посоветовать книгу, аудиокнигу и многое другое!";
		}

		//С помощью messages.send отправляем ответное сообщение
		$request_params = array(
		'message' => $resp_message,
		'peer_id' => $user_id,
		'access_token' => $token,
		'v' => '5.103',
		'random_id' => '0',
		'attachment' => $attachment,
		);

		$get_params = http_build_query($request_params);

		file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);

		//Возвращаем "ok" серверу Callback API

		echo('ok');

	break; 

} 
?>