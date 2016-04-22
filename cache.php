<?php
$apiKey = getenv('api')? : die("no api key");
require('vendor/autoload.php');
//read AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY from env vars
$s3 = Aws\S3\S3Client::factory();
$bucket = getenv('S3_BUCKET')?: die('No "S3_BUCKET" config var in found in env!');

//cache files in s3
$static_file_exists = $s3->doesObjectExist($bucket, 'static_result.data');
if ($static_file_exists) {
  $staticObject = $s3->getObject(array(
    'Bucket' => $bucket,
    'Key' => 'static_result.data'
  ));
  $body = $staticObject['Body'];
  $data = unserialize($body);
    if ($data['timestamp'] > time() - 6 * 60 * 60) {
          $static_result = $data['static_result'];
     } 
}
if (!isset($static_result)) { // cache doesn't exist or is older than 6 hours mins
    $static_result = file_get_contents('https://global.api.pvp.net/api/lol/static-data/na/v1.2/realm?api_key='.$apiKey); // or whatever your API call is
    $static_data = json_decode($static_result, true);
    $data = array ('static_result' => $static_data, 'timestamp' => time());
    $static_file = serialize($data);
    $upload = $s3->putObject(array(
      'Bucket' => $bucket, 
      'Key' => 'static_result.data',
      'Body' => $static_file
    ));
}
//current version of datadragon
$dd = $data['static_result']['dd'];

//cache champions data
$champ_file_exists = $s3->doesObjectExist($bucket, 'champ_result.data');
if ($champ_file_exists) {
  $champObject = $s3->getObject(array(
    'Bucket' => $bucket,
    'Key' => 'champ_result.data'
  ));
  $champBody = $champObject['Body'];
  $champData = unserialize($champBody);
      if ($champData['timestamp'] > time() - 6 * 60 * 60) {
          $champ_result = $champData['champ_info'];
      } 
} 
if (!isset($champ_result)) { // cache doesn't exist or is older than 6 hours
    $champ_result = file_get_contents('https://global.api.pvp.net/api/lol/static-data/na/v1.2/champion?champData=image&api_key='.$apiKey); // or whatever your API call is
    $champ_data = json_decode($champ_result, true);
    $champData = array ('champ_info' => $champ_data, 'timestamp' => time());
    $champ_file = serialize($champData);
    $upload = $s3->putObject(array(
      'Bucket' => $bucket, 
      'Key' => 'champ_result.data',
      'Body' => $champ_file
    ));
}
//cache items data
$item_file_exists = $s3->doesObjectExist($bucket, 'item_result.data');
if ($item_file_exists) {
  $itemObject = $s3->getObject(array(
    'Bucket' => $bucket,
    'Key' => 'item_result.data'
  ));
    $itemBody = $itemObject['Body'];
    $itemData = unserialize($itemBody);
    if ($itemData['timestamp'] > time() - 4 * 60 * 60) {
        $item_result = $itemData['item_info'];
    } 
}
if (!isset($item_result)) { // cache doesn't exist or is older than 10 mins
    $item_result = file_get_contents('https://global.api.pvp.net/api/lol/static-data/na/v1.2/item?itemListData=all&api_key='.$apiKey); // or whatever your API call is
    $item_data = json_decode($item_result, true);
    $itemData = array ('item_info' => $item_data, 'timestamp' => time());
    $item_file = serialize($itemData);
    $upload = $s3->putObject(array(
      'Bucket' => $bucket, 
      'Key' => 'item_result.data',
      'Body' => $item_file
    ));
}