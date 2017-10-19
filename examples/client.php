<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);


require __DIR__.'/../vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();

/*
// Options for connection, these are the same as "React\Socket\Connector"
    'tcp' => $tcp,
    'tls' => $tls,
    'unix' => $unix,
    'dns' => false,
    'timeout' => false
    ...
*/    

// In this example we do not want to verify the peer or name for TLS, and we want to timeout the connection
$options=array('tls'=>array(
    'verify_peer' => false,
    'verify_peer_name' => false
    ),
    'timeout'=>5.0                      /* 'timeout'=> false for disable */ 
);


//
// New interface for Bolt Client matches "React\Socket\Connector" connector, we now specify the URL in connect
//
$client = new \Calcinai\Bolt\Client($loop, $options);

$client->setOrigin('127.0.0.1');

//
// Set 'on' events, stateChange, message
//
$client->on('stateChange', function($newState){
    //
    // Stats are connecting, connected, closing, closed.
    //
    echo "State changed to: $newState\n";
});

$client->on('message', function($message) use ($client){
    echo "message: $message\n";
});

$client->on('error', function ($error) use ($client){

   echo 'Error: ' . $error . PHP_EOL;
    
});

//
// connect specifies the URL to match "React\Socket\Connector" 
//
// specify ws:// or wss://
//
//$client->connect('ws://proxy20.remot3.iit:8089/test123sub?id=xyzzy');
$client->connect('wss://proxy20.remot3.it:8443/rest123sub?id=xyzzy');
$loop->run();

echo "bye\n";


