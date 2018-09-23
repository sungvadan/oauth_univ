<?php

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$http = new Client('http://coop.apps.symfonycasts.com', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

$request = $http->post('/token', null,[
    'client_id' => 	'SVD test',
    'client_secret' => 'c8cc2f6c8a6317538c250178e225044e',
    'grant_type' => 'client_credentials',
]);
$response = $request->send();
$responseBody = $response->getBody(true);
$responseArr = json_decode($responseBody, true);
$accessToken = $responseArr['access_token'];

$request = $http->post('/api/12/eggs-collect');
$request->addHeader('Authorization', 'Bearer '.$accessToken);
$response = $request->send();

echo $response->getBody();
echo '\n\n';