<?php

include("settings_t.php");
// POST HANDLER -->
$telegramtk=TELEGRAM_BOT; // inserire il token


    $file_id = $_GET['id'];

if ($file_id == null){
  echo "nessun allegato disponibile";
  exit;
}
$rawData = file_get_contents("https://api.telegram.org/bot".$telegramtk."/getFile?file_id=".$file_id);
$obj=json_decode($rawData, true);
var_dump($obj);


?>
