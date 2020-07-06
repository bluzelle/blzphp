<?php

require __DIR__ . '/vendor/autoload.php';

use Bluzelle\Client;

function printN($str = '')
{
	echo $str . PHP_EOL;
}

$client = new Client(
    'bluzelle1upsfjftremwgxz3gfy0wf3xgvwpymqx754ssu9',
    'around buzz diagram captain obtain detail salon mango muffin brother morning jeans display attend knife carry green dwarf vendor hungry fan route pumpkin car',
    'http://testnet.public.bluzelle.com:1317',
    'bluzelleTestPublic-1',
    '20fc19d4-7c9d-4b5c-9578-8cedd756e0ea'
);

$gasInfo = ['max_fee' => 4000000];

printN('#version start');
printN($client->version());
printN();

printN('#account start');
printN(json_encode($client->account()));
printN();

printN('#deleteAll start');
$client->deleteAll($gasInfo);
printN('#deleteAll done');
printN();

printN("#create start: create key: 'key', value: 'v' ");
$client->create('key', 'v', $gasInfo);
printN('#create end');
printN();

printN('#read start');
printN($client->read('key'));
printN();

printN('#txRead start');
printN($client->txRead('key', $gasInfo));
printN();

printN("#update start: update key: 'key', value: 'v2'");
$client->update('key', 'v2', $gasInfo);
printN('#update end');
printN();

printN('#read: read new value');
printN($client->read('key'));
printN();

printN("#has has key: 'key'");
printN(json_encode($client->has('key')));
printN();

printN("#txHas has key: 'key'");
printN(json_encode($client->txHas('key', $gasInfo)));
printN();

printN('#keys start');
printN(json_encode($client->keys()));
printN();

printN('#txKeys start');
printN(json_encode($client->txKeys($gasInfo)));
printN();

printN("#rename: rename key: 'key', new_key: 'new_key'");
$client->rename('key', 'new_key', $gasInfo);
printN('#rename end');
printN();

printN("#read: read from new key name");
printN($client->read('new_key'));
printN();

printN('#count start');
printN(json_encode($client->count()));
printN();

printN('#txCount start');
printN(json_encode($client->txCount($gasInfo)));
printN();

printN('#keyValues start');
printN(json_encode($client->keyValues()));
printN();

printN('#txKeyValues start');
printN(json_encode($client->txKeyValues($gasInfo)));
printN();

printN('#getLease start');
printN($client->getLease('new_key'));
printN();

printN('#txGetLease start');
printN($client->txGetLease('new_key', $gasInfo));
printN();

printN('#renewLease');
$client->renewLease('new_key', [ 'days' => 1 ], $gasInfo);

printN('#getLease');
printN($client->getLease('new_key'));

printN('#renewLeaseAll');
$client->renewLeaseAll([ 'days' => 2 ], $gasInfo);

printN('#getLease');
printN("key: new_key, lease: " . $client->getLease('new_key'));

printN('#getNShortestLeases');
printN(json_encode($client->getNShortestLeases(5)));

printN('#txGetNShortestLeases'); 
printN(json_encode($client->txGetNShortestLeases(5, $gasInfo)));

$client->create('key2', 'value2', $gasInfo);

printN('#multiupdate');
$client->multiupdate([ ['key' => 'new_key', 'value' => 'v1'], [ 'key' => 'key2', 'value' => 'v2' ] ], $gasInfo);

printN(json_encode($client->keyValues()));
