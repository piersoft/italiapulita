<?php
/**
* Telegram Bot example for mapping "Degrado Urbano".
* @author Francesco Piero Paolicelli
*/
include("settings_t.php");
include("Telegram.php");

class mainloop{
const MAX_LENGTH = 4096;
function start($telegram,$update)
{

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
	//$data=new getdata();
	// Instances the class
	$db = new PDO(DB_NAME);

	/* If you need to manually take some parameters
	*  $result = $telegram->getData();
	*  $text = $result["message"] ["text"];
	*  $chat_id = $result["message"] ["chat"]["id"];
	*/

	$first_name=$update["message"]["from"]["first_name"];
	$text = $update["message"] ["text"];
	$chat_id = $update["message"] ["chat"]["id"];
	$user_id=$update["message"]["from"]["id"];
	$location=$update["message"]["location"];
	$reply_to_msg=$update["message"]["reply_to_message"];

	$this->shell($telegram, $db,$first_name,$text,$chat_id,$user_id,$location,$reply_to_msg);
	//$db = NULL;

}

//gestisce l'interfaccia utente
 function shell($telegram,$db,$first_name,$text,$chat_id,$user_id,$location,$reply_to_msg)
{
	$csv_path=dirname(__FILE__).'/./db/map_data.txt';
	$db_path=dirname(__FILE__).'/./db/db.sqlite';

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");

	if ($text == "/start" || $text == "info" || $text == "©️info") {
		$reply = "Benvenuto ".$first_name.". Questo Bot è stato realizzato da @piersoft e permette le segnalazioni di 'Degrado urbano' classiche in molte parti del Bel Paese (verde, giardini incolti, manto stradale rovinato ect), purtroppo. L'autore non è responsabile per l'uso improprio di questo strumento e dei contenuti degli utenti. Inviando le segnalazioni si è consapevoli che l'utente e la sua segnalazione (univoci su Telegram) vengono registrati e visualizzati pubblicamente su mappa con licenza CC0. La geocodifca avviene grazie al database Nominatim di openStreeMap con licenza oDBL";
		$content = array('chat_id' => $chat_id, 'text' => $reply);
		$telegram->sendMessage($content);


		$forcehide=$telegram->buildKeyBoardHide(true);
		$content = array('chat_id' => $chat_id, 'text' => "", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
		$bot_request_message=$telegram->sendMessage($content);



		$log=$today. ",new chat started," .$chat_id. "\n";

	}elseif ($text == "/location" || $text == "🌐posizione") {

		$option = array(array($telegram->buildKeyboardButton("Invia la tua posizione / send your location", false, true)) //this work
											);
	// Create a permanent custom keyboard
	$keyb = $telegram->buildKeyBoard($option, $onetime=false);
	$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Attiva la localizzazione sul tuo smartphone / Turn on your GPS");
	$telegram->sendMessage($content);
	exit;
	}else if ($text == "/istruzioni" || $text == "istruzioni" || $text == "❓istruzioni") {
/*
		$reply = "http://goo.gl/RX0dzr";
		$content = array('chat_id' => $chat_id, 'text' => $reply);
		$telegram->sendMessage($content);
		$reply = "Clicca la \xF0\x9F\x93\x8E --> Posizione e poi clicca \xF0\x9F\x93\x8E --> File\n";
		$content = array('chat_id' => $chat_id, 'text' => $reply);
		$telegram->sendMessage($content);
		*/
		$img = curl_file_create('istruzioni.png','image/png');
		$contentp = array('chat_id' => $chat_id, 'photo' => $img);
		$telegram->sendPhoto($contentp);
		$content = array('chat_id' => $chat_id, 'text' => "[Immagine realizzata da Alessandro Ghezzer]");
		$telegram->sendMessage($content);

		$log=$today. ",istruzioni," .$chat_id. "\n";

	}
		elseif ($text=="cancella" || $text =="/cancella" || $text =="❌cancella" )
			{

				$reply = "Per cancellare una tua segnalazione digita c:numerosegnalazione, esempio c:699";
				$content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);

			}
			elseif (strpos($text,'c:') !== false) {
				$text=str_replace("c:","",$text);


			//	$user_id = "193317621";
		   	$statement = "DELETE FROM ". DB_TABLE_GEO ." where bot_request_message = '" . $text . "' AND user = '".$user_id."'";
		   	$db->exec($statement);
					$reply = "Segnalazione n° ".$text." è stata cancellata";
					$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
					$log=$today. ",segnalazione cancellata," .$chat_id. "\n";


				}
		//gestione segnalazioni georiferite
		elseif($location!=null)

		{

			$this->location_manager($db,$telegram,$user_id,$chat_id,$location);
			exit;

		}
//elseif($text !=null)

else //($reply_to_msg != NULL)
{
if ($reply_to_msg != NULL){

	$response=$telegram->getData();

	$type=$response["message"]["video"]["file_id"];
	$text =$response["message"]["text"];
	$risposta="";
	$file_name="";
	$file_path="";
	$file_name="";


if ($type !=NULL) {
$file_id=$type;
$text="video allegato";
$risposta="ID dell'allegato:".$file_id."\n";
$content = array('chat_id' => $chat_id, 'text' => "per inviare un allegato devi cliccare \xF0\x9F\x93\x8E e poi File non video/foto/audio");
$telegram->sendMessage($content);
exit;
}

$file_id=$response["message"]["photo"][0]["file_id"];

if ($file_id !=NULL) {

$telegramtk=TELEGRAM_BOT; // inserire il token
$rawData = file_get_contents("https://api.telegram.org/bot".$telegramtk."/getFile?file_id=".$file_id);
$obj=json_decode($rawData, true);
$file_path=$obj["result"]["file_path"];
$caption=$response["message"]["caption"];
if ($caption != NULL) $text=$caption;
$risposta="ID dell'allegato: ".$file_id."\n";
$content = array('chat_id' => $chat_id, 'text' => "per inviare un allegato devi cliccando \xF0\x9F\x93\x8E e poi File non video/foto/audio");
$telegram->sendMessage($content);
exit;
}
$typed=$response["message"]["document"]["file_id"];

if ($typed !=NULL){
$file_id=$typed;
$file_name=$response["message"]["document"]["file_name"];
$text="documento: ".$file_name." allegato";
$risposta="ID dell'allegato:".$file_id."\n";

}

$typev=$response["message"]["voice"]["file_id"];
if ($typev !=NULL){
$file_id=$typev;
$text="audio allegato";
$risposta="ID dell'allegato:".$file_id."\n";
$content = array('chat_id' => $chat_id, 'text' => "per inviare un allegato devi cliccando \xF0\x9F\x93\x8E e poi File non video/foto/audio");
$telegram->sendMessage($content);
exit;
}
$csv_path='db/map_data.txt';
$db_path='db/db.sqlite';
//echo $db_path;
$username=$response["message"]["from"]["username"];
$first_name=$response["message"]["from"]["first_name"];

$db1 = new SQLite3($db_path);
$q = "SELECT lat,lng FROM ".DB_TABLE_GEO ." WHERE bot_request_message='".$reply_to_msg['message_id']."'";
$result=	$db1->query($q);
$row = array();
$i=0;

while($res = $result->fetchArray(SQLITE3_ASSOC))
		{

				if(!isset($res['lat'])) continue;

				 $row[$i]['lat'] = $res['lat'];
				 $row[$i]['lng'] = $res['lng'];
				 $i++;
		 }

		 //inserisce la segnalazione nel DB delle segnalazioni georiferite
			 $statement = "UPDATE ".DB_TABLE_GEO ." SET text='".$text."',file_id='". $file_id ."',filename='". $file_name ."',first_name='". $first_name ."',file_path='". $file_path ."',username='". $username ."' WHERE bot_request_message ='".$reply_to_msg['message_id']."'";
			 print_r($reply_to_msg['message_id']);
			 $db->exec($statement);
		 //		$this->create_keyboard_temp($telegram,$chat_id);

	//	 $reply = "La segnalazione n° ".$reply_to_msg['message_id']." è stata Registrata.\n".$risposta."Grazie!\n";

	  $reply = "La segnalazione n° ".$reply_to_msg['message_id']." è stata Registrata.\nGrazie!\n";
 		$reply .= "Puoi visualizzarla su :\nhttp://www.piersoft.it/italiapulitabot/#18/".$row[0]['lat']."/".$row[0]['lng'];
 		$content = array('chat_id' => $chat_id, 'text' => $reply);
 		$telegram->sendMessage($content);
 		$log=$today. ",information for maps recorded," .$chat_id. "\n";

 		exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');


	}
 	//comando errato

 	else{

 		 $reply = "Hai selezionato un comando non previsto. Ricordati che devi prima inviare la tua posizione";
 		 $content = array('chat_id' => $chat_id, 'text' => $reply);
 		 $telegram->sendMessage($content);

 		 $log=$today. ",wrong command sent," .$chat_id. "\n";

 	 }
}
 	//aggiorna tastiera
 	$this->create_keyboard($telegram,$chat_id);
 	//log
 	file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);

 }



// Crea la tastiera
function create_keyboard($telegram, $chat_id)
 {
	 			$option = array(["❓istruzioni","❌cancella"],["🌐posizione","©️info"]);
				$keyb = $telegram->buildKeyBoard($option, $onetime=true);
				$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[guarda la mappa delle segnalazioni su http://www.piersoft.it/italiapulitabot/ oppure invia la tua segnalazione cliccando \xF0\x9F\x93\x8E]");
				$telegram->sendMessage($content);

 }




function location_manager($db,$telegram,$user_id,$chat_id,$location)
	{

			$lng=$location["longitude"];
			$lat=$location["latitude"];


			$reply="http://nominatim.openstreetmap.org/reverse?email=piersoft2@gmail.com&format=json&lat=".$lat."&lon=".$lng."&zoom=18&addressdetails=1";
			$json_string = file_get_contents($reply);
			$parsed_json = json_decode($json_string);
			//var_dump($parsed_json);
			$temp_c1 =$parsed_json->{'display_name'};
			if ($parsed_json->{'address'}->{'city'}) {
			//  $temp_c1 .="\ncittà: ".$parsed_json->{'address'}->{'city'};

			}

			$response=$telegram->getData();

			$bot_request_message_id=$response["message"]["message_id"];
			$time=$response["message"]["date"]; //registro nel DB anche il tempo unix

			$h = "1";// Hour for time zone goes here e.g. +7 or -4, just remove the + or -
			$hm = $h * 60;
			$ms = $hm * 60;
			$timec=gmdate("Y-m-d\TH:i:s\Z", $time+($ms));
			$timec=str_replace("T"," ",$timec);
			$timec=str_replace("Z"," ",$timec);
			//nascondo la tastiera e forzo l'utente a darmi una risposta

	//		$forcehidek=$telegram->buildKeyBoardHide(true);
	//	  $content = array('chat_id' => $chat_id, 'text' => "Cosa vuoi comunicarci in questo posto?", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
						$content = array('chat_id' => $chat_id, 'text' => "Cosa vuoi comunicarmi in ".$temp_c1."?", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);

		  $bot_request_message=$telegram->sendMessage($content);

		      	$forcehide=$telegram->buildForceReply(true);

		  			//chiedo cosa sta accadendo nel luogo
		// 		$content = array('chat_id' => $chat_id, 'text' => "[Scrivici cosa sta accadendo qui]", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);

		        $content = array('chat_id' => $chat_id, 'text' => "[scrivi il tuo messaggio o invia un FILE]", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);

//			$forcehide=$telegram->buildForceReply(true);

			//chiedo cosa sta accadendo nel luogo
	//	$content = array('chat_id' => $chat_id, 'text' => "[Cosa vuoi comunicarmi in questo luogo?".$lat.",".$lng, 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
			$bot_request_message=$telegram->sendMessage($content);


			//memorizzare nel DB
			$obj=json_decode($bot_request_message);
			$id=$obj->result;
			$id=$id->message_id;

			//print_r($id);
			$statement = "INSERT INTO ". DB_TABLE_GEO. " (lat,lng,user,username,text,bot_request_message,time,file_id,file_path,filename,first_name) VALUES ('" . $lat . "','" . $lng . "','" . $user_id . "',' ',' ','". $id ."','". $timec ."',' ',' ',' ',' ')";
			$db->exec($statement);


	}


}

?>
