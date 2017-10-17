<?php

require __DIR__.'/../vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();
$dns_factory = new React\Dns\Resolver\Factory();
$resolver = $dns_factory->createCached('8.8.8.8', $loop);

$context=array(
    'verify_peer' => false,
    'verify_peer_name' => false
);

//
// New interface for Bolt Client matches connector but with URL prepended.
// (URL,$loop,$options);
//
//$client = new \Calcinai\Bolt\Client('wss://127.0.0.1:8443/test123sub?id=xyzzy', $loop, array() ,$context);
//$client = new \Calcinai\Bolt\Client('wss://proxy20.remot3.it:8443/test123sub?id=xyzzy', $loop, array() ,$context);
$client = new \Calcinai\Bolt\Client('ws://proxy20.remot3.it:8088/test123sub?id=xyzzy', $loop, array() ,$context);
//$client = new \Calcinai\Bolt\Client('wss://x.p72.rt3.io:8443/test123sub?id=xyzzy', $loop, array(), $context );
//$client = new \Calcinai\Bolt\Client('wss://proxy20.remot3.it:8443/test123sub?id=xyzzy', $loop, array(), array() );
$client->setOrigin('127.0.0.1');

$client->on('stateChange', function($newState){
    echo "State changed to: $newState\n";
});

$client->on('message', function($message) use ($client){
    echo "message: $message\n";
});


$client->on('data', function($message) use ($client){
        echo "message: $message\n";
});

$client->on('error', function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    if ($e->getPrevious() !== null) {
        $previousException = $e->getPrevious();
        echo $previousException->getMessage() . PHP_EOL;
    }
});

//
// connect like socket?
$client->connect();
$loop->run();

echo "bye\n";

//print_r($client);

