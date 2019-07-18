<?php

require_once __DIR__ . '/vendor/autoload.php';

function errHandle($errNo, $errStr, $errFile, $errLine) {
    $msg = "$errStr in $errFile on line $errLine";
    if ($errNo == E_NOTICE || $errNo == E_WARNING) {
        throw new ErrorException($msg, $errNo);
    } else {
        echo $msg;
    }
}

set_error_handler('errHandle');

$config = [
    'apikey' => 'l7xx8e4a4c4fa35046bfac7351526d930f09',
    'tab' => 'bcl_only',
    'scope' => 'bcl',
    'vid' => 'bclib_new',
    'url' => 'https://api-na.hosted.exlibrisgroup.com'
];

$redis = \Symfony\Component\Cache\Adapter\RedisAdapter::createConnection(
    'redis://localhost'
);
$cache = new \Symfony\Component\Cache\Adapter\RedisAdapter($redis);

$guzzle = new \GuzzleHttp\Client(['base_uri' => $config['url']]);
$api_client = new \BCLib\PrimoClient\ApiClient($guzzle);
$books_config = new \BCLib\PrimoClient\QueryConfig($config['apikey'], $config['tab'], $config['vid'], $config['scope']);
$thumb_service = new \App\Service\VideoThumbService($cache);

$guzzle_thumbs = new GuzzleHttp\Client();
$medici = new \App\Service\MediciTVVideoProvider($guzzle_thumbs);
$films = new \App\Service\FilmsOnDemandProvider();
$alex = new \App\Service\AlexanderStreetVideoProvider($guzzle_thumbs);
$thumb_service->addProvider($medici);
$thumb_service->addProvider($films);
$thumb_service->addProvider($medici);

$search = new \App\Service\VideoSearch($books_config, $api_client);

$result = $search->search('aria', 3);

$docs=  $result->docs;

$thumbs = $thumb_service->fetch($docs);

var_dump($thumbs);