<?php
require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
use \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use \LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// set false for production
$pass_signature = true;

// set LINE channel_access_token and channel_secret
$channel_access_token = getenv("catheroku");
$channel_secret = getenv("csheroku");

// inisiasi objek bot
//include 'codenya.php';
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
$configs =  [
  'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);
$bot->getProfile(userId);
$bot->getMessageContent(messageId);
// buat route untuk url homepage
$app->get('/', function($req, $res)
{
  echo "Welcome at Slim Framework";
});

// buat route untuk webhook
$app->post('/webhook', function ($request, $response) use ($bot, $pass_signature)
{
  // get request body and line signature header
  $body        = file_get_contents('php://input');
  $signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : '';

  // log body and signature
  file_put_contents('php://stderr', 'Body: '.$body);

  if($pass_signature === false)
  {
    // is LINE_SIGNATURE exists in request header?
    if(empty($signature)){
      return $response->withStatus(400, 'Signature not set');
    }

    // is this request comes from LINE?
    if(! SignatureValidator::validateSignature($body, $channel_secret, $signature)){
      return $response->withStatus(400, 'Invalid signature');
    }
  }

  // kode aplikasi nanti disini
  $data = json_decode($body, true);
  if(is_array($data['events'])){
    foreach ($data['events'] as $event)
    {
      if ($event['type'] == 'message')
      {
        $userId     = $event['source']['userId'];
        $groupId     = $event['source']['groupId'];
        $getprofile = $bot->getProfile($userId);
        $profile    = $getprofile->getJSONDecodedBody();
        $greetings  = new TextMessageBuilder("Halo, ".$profile['displayName']);
        $a = (explode('-',$event['message']['text']));
        if ($a[0]=="/userid") {
          $result = $bot->replyText($event['replyToken'], $userId);
        }
        if ($a[0]=="/groupid") {
          $result = $bot->replyText($event['replyToken'], $event['source']['groupId']);
        }
        if ($a[0]=="/sms") {
          $xml = file_get_contents(getenv("smsapi").urlencode($a[1])."&pesan=".urlencode($a[2]));
          $json = json_encode($xml);
          $array = json_decode($json,TRUE);
          $result = $bot->replyText($event['replyToken'], var_dump($array));
        }
        else if ($a[0]=="/jadwal") {
          $kota=(isset($a[1])) ? $a[1] : "malang";
          $stored = file_get_contents("http://api.aladhan.com/v1/timingsByCity?city=$kota&country=indonesia&method=11");
          $datanya = json_decode($stored, TRUE);
          $jadwalsholat=$datanya['data']['timings'];
          $hijri=$datanya['data']['date'];
          $hasilnya="Jadwal Sholat \nWilayah ".$kota.", ".$hijri['readable']
          ."\n================"
          ."\nImsak : ".$jadwalsholat['Imsak']
          ."\nSubuh : ".$jadwalsholat['Fajr']
          ."\nDhuhur : ".$jadwalsholat['Dhuhr']
          ."\nAshar : ".$jadwalsholat['Asr']
          ."\nMaghrib : ".$jadwalsholat['Maghrib']
          ."\nIsha' : ".$jadwalsholat['Isha']
          ."\n================"
          ."\n".$hijri['hijri']['day']." ".$hijri['hijri']['month']['en']." ".$hijri['hijri']['year'];
          $result = $bot->replyText($event['replyToken'],$hasilnya);
        }
        else if (substr($event['message']['text'],0,5)=='<?php') {
          $data = array(
            'php' => $event['message']['text']
          );
          $babi=file_get_contents('http://farkhan.000webhostapp.com/nutshell/babi.php?'.http_build_query($data));
          $result = $bot->replyText($event['replyToken'], $babi);
        }

        // Coba
        if($a[0] == "/coba1"){
          $buttonTemplateBuilder = new ButtonTemplateBuilder(
            "title",
            "text",
            "https://i0.wp.com/angryanimebitches.com/wp-content/uploads/2013/03/tamakomarket-overallreview-tamakoanddera.jpg",
            [
              new MessageTemplateActionBuilder('Action Button','action'),
            ]
          );
          $templateMessage = new TemplateMessageBuilder('nama template', $buttonTemplateBuilder);
          $result = $bot->replyMessage($event['replyToken'], $templateMessage);
        }
        if($a[0] == "/coba2"){
          $confirmTemplateBuilder = new ConfirmTemplateBuilder(
            "coba confirm?",
            [
              new MessageTemplateActionBuilder('Ya',"/ya"),
              new MessageTemplateActionBuilder('Tidak','/tidak'),
            ]
          );
          $templateMessage = new TemplateMessageBuilder('nama template', $confirmTemplateBuilder);
          $result = $bot->replyMessage($event['replyToken'], $templateMessage);
        }
        if($a[0] == "/coba3"){
          $carouselTemplateBuilder = new CarouselTemplateBuilder([
            new CarouselColumnTemplateBuilder("title", "text","https://i0.wp.com/angryanimebitches.com/wp-content/uploads/2013/03/tamakomarket-overallreview-tamakoanddera.jpg",[
              new UriTemplateActionBuilder('buka',"http://hilite.me/"),
            ]),
            new CarouselColumnTemplateBuilder("title", "text","https://i0.wp.com/angryanimebitches.com/wp-content/uploads/2013/03/tamakomarket-overallreview-tamakoanddera.jpg",[
              new UriTemplateActionBuilder('Buka',"http://hilite.me/"),
            ]),
          ]);
          $templateMessage = new TemplateMessageBuilder('nama template',$carouselTemplateBuilder);
          $result = $bot->replyMessage($event['replyToken'], $templateMessage);
        }
        if($a[0] == "/coba4"){
          $ImageCarouselTemplateBuilder = new ImageCarouselTemplateBuilder([
            new ImageCarouselColumnTemplateBuilder(
              "https://i0.wp.com/angryanimebitches.com/wp-content/uploads/2013/03/tamakomarket-overallreview-tamakoanddera.jpg",
              new UriTemplateActionBuilder(
                'Buka Browser',
                "https://i0.wp.com/angryanimebitches.com/wp-content/uploads/2013/03/tamakomarket-overallreview-tamakoanddera.jpg")
              ),
              new ImageCarouselColumnTemplateBuilder(
                "https://i0.wp.com/angryanimebitches.com/wp-content/uploads/2013/03/tamakomarket-overallreview-tamakoanddera.jpg",
                new UriTemplateActionBuilder(
                  'Buka Browser',
                  "https://i0.wp.com/angryanimebitches.com/wp-content/uploads/2013/03/tamakomarket-overallreview-tamakoanddera.jpg")
                ),
              ]);
              $templateMessage = new TemplateMessageBuilder('nama template',$ImageCarouselTemplateBuilder);
              $result = $bot->replyMessage($event['replyToken'], $templateMessage);
            }

            // just admin can do this command
            // else if ($groupId=="Cc8b2bfac9663e478776591b093176936" || $groupId=="Cba854d5ed4cd22b2279b09708b7a79a8") {
            //     if ($a[0]=="/getdata" && $a[1]!="165150700111005") {
            //         $output=file_get_contents(getenv("apisiam").$a[1]);
            //         $datanya = (json_decode($output, true));
            //         $hasilnya="Detail Data Mahasiswa \nNIM ".$datanya['nim']
            //         ."\n================"
            //         ."\nNama : ".$datanya['nama']
            //         ."\nTTL : ".$datanya['ttl']
            //         // ."\nAgama : ".$datanya['agama']
            //         ."\nFakultas : ".$datanya['fak']
            //         ."\nProdi : ".$datanya['prod']
            //         ."\nAngkatan : ".$datanya['ang']
            //         ."\n================";
            //         $result = $bot->replyText($event['replyToken'], $hasilnya);
            //     }
            // }
            else if ($userId=="U4f3b524bfcd08556173108d04ae067ad") {
              if ($a[0]=="/ktpkk") {
                $stored = file_get_contents('http://farkhan.000webhostapp.com/nutshell/read.php?AksesToken='.getenv("csheroku"));
                $obj = json_decode($stored, TRUE);
                $result = $bot->replyText($event['replyToken'], $obj['Data'][0]['nik_kk']);
              }else if ($a[0]=="/getdata" && $a[1]!="165150700111005") {
                $output=file_get_contents(getenv("apisiam").$a[1]);
                $datanya = (json_decode($output, true));
                $hasilnya="Detail Data Mahasiswa \nNIM ".$datanya['nim']
                ."\n================"
                ."\nNama : ".$datanya['nama']
                ."\nTTL : ".$datanya['ttl']
                // ."\nAgama : ".$datanya['agama']
                ."\nFakultas : ".$datanya['fak']
                ."\nProdi : ".$datanya['prod']
                ."\nAngkatan : ".$datanya['ang']
                ."\nCluster : ".$datanya['clus']
                ."\n================";
                $result = $bot->replyText($event['replyToken'], $hasilnya);
              }

            }
            if(
              $event['source']['type'] == 'group' or
              $event['source']['type'] == 'room'
            ){
              if($event['source']['userId']){
                if ($a[0]=="/tambah") {
                  $stored = file_get_contents('http://farkhan.000webhostapp.com/tae/storeData.php?groupid='.$event['source']['groupId'].'&nama_jadwal='.urlencode($a[1]).'&isi_jadwal='.urlencode($a[2]));
                  $obj = json_decode($stored, TRUE);
                  $result = $bot->replyText($event['replyToken'], $obj['message']);
                }
                else if ($a[0]=="/semua") {
                  $stored = file_get_contents('http://farkhan.000webhostapp.com/tae/GetData.php?groupid='.$event['source']['groupId']);
                  $datanya = json_decode($stored, TRUE);
                  $hasilnya="Note Yang Disimpan";
                  if (is_array($datanya) || is_object($datanyas)) {
                    foreach ($datanya as $datanyas) {
                      echo $datanyas['jadwal'];
                      foreach($datanyas as $datanyass)
                      {
                        $hasilnya=$hasilnya."\n".$datanyass['nama_jadwal'];
                      }
                    }
                  }
                  $result = $bot->replyText($event['replyToken'],$hasilnya);
                }else if ($a[0]=="/detail") {
                  $stored = file_get_contents('http://farkhan.000webhostapp.com/tae/GetData.php?groupid='.$event['source']['groupId'].'&nama_jadwal='.urlencode($a[1]));
                  $datanya = json_decode($stored, TRUE);
                  $hasilnya="Detail Note ".$a[1];
                  if (is_array($datanya) || is_object($datanyas)) {
                    foreach ($datanya as $datanyas) {
                      echo $datanyas['jadwal'];
                      foreach($datanyas as $datanyass)
                      {
                        $hasilnya=$hasilnya."\n".$datanyass['detail'];
                      }
                    }
                  }
                  $result = $bot->replyText($event['replyToken'],$hasilnya);
                }else if ($a[0]=="/hapus") {
                  $stored = file_get_contents('http://farkhan.000webhostapp.com/tae/deleteNote.php?groupid='.$event['source']['groupId'].'&nama_jadwal='.urlencode($a[1]));
                  $obj = json_decode($stored, TRUE);
                  $result = $bot->replyText($event['replyToken'], $obj['message']);
                }
                return $res->withJson($result->getJSONDecodedBody(), $event['message']['text'].$result->getHTTPStatus());
              } else {
                if (substr($event['message']['text'],0,2)=='IP' & strlen($event['message']['text'])==18){
                  $result = $bot->replyText($event['replyToken'], 'Add terlebih dahulu');
                }
                return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
              }
            } else {
              if($event['message']['type'] == 'text'){
                if ($a[0]=="/tambah") {
                  $stored = file_get_contents('http://farkhan.000webhostapp.com/tae/storeData.php?groupid='.$event['source']['userId'].'&nama_jadwal='.urlencode($a[1]).'&isi_jadwal='.urlencode($a[2]));
                  $obj = json_decode($stored, TRUE);
                  $result = $bot->replyText($event['replyToken'], $obj['message']);
                }
                else if ($a[0]=="/semua") {
                  $stored = file_get_contents('http://farkhan.000webhostapp.com/tae/GetData.php?groupid='.$event['source']['userId']);
                  $datanya = json_decode($stored, TRUE);
                  $hasilnya="Note Yang Disimpan";
                  if (is_array($datanya) || is_object($datanyas)) {
                    foreach ($datanya as $datanyas) {
                      echo $datanyas['jadwal'];
                      foreach($datanyas as $datanyass)
                      {
                        $hasilnya=$hasilnya."\n".$datanyass['nama_jadwal'];
                      }
                    }
                  }
                  $result = $bot->replyText($event['replyToken'],$hasilnya);
                }else if ($a[0]=="/detail") {
                  $stored = file_get_contents('http://farkhan.000webhostapp.com/tae/GetData.php?groupid='.$event['source']['userId'].'&nama_jadwal='.urlencode($a[1]));
                  $datanya = json_decode($stored, TRUE);
                  $hasilnya="Detail Note ".$a[1];
                  if (is_array($datanya) || is_object($datanyas)) {
                    foreach ($datanya as $datanyas) {
                      echo $datanyas['jadwal'];
                      foreach($datanyas as $datanyass)
                      {
                        $hasilnya=$hasilnya."\n".$datanyass['detail'];
                      }
                    }
                  }
                  $result = $bot->replyText($event['replyToken'],$hasilnya);
                }else if ($a[0]=="/hapus") {
                  $stored = file_get_contents('http://farkhan.000webhostapp.com/tae/deleteNote.php?groupid='.$event['source']['userId'].'&nama_jadwal='.urlencode($a[1]));
                  $obj = json_decode($stored, TRUE);
                  $result = $bot->replyText($event['replyToken'], $obj['message']);
                }

              }
            }
          }
        }
      }
    });
    $app->run();
