<?php

use Bluzelle\Client;

require __DIR__ . '/../vendor/autoload.php';

$client = new Client(
    'bluzelle1upsfjftremwgxz3gfy0wf3xgvwpymqx754ssu9',
    'around buzz diagram captain obtain detail salon mango muffin brother morning jeans display attend knife carry green dwarf vendor hungry fan route pumpkin car',
    'http://testnet.public.bluzelle.com:1317',
    'bluzelle',
    '20fc19d4-7c9d-4b5c-9578-8cedd756e0ea'
);

$gasInfo = ['max_fee' => 4000000];

$client->version();
// 0.0.0-60-g1b32db7

$client->account();
# {"address"=>"bluzelle1upsfjftremwgxz3gfy0wf3xgvwpymqx754ssu9",
# "coins"=>[{"denom"=>"ubnt", "amount"=>"1199722793983680"}],
# "public_key"=>"bluzellepub1addwnpepqwnm94uc0yy338w7l3ghd8en0kg6nvds3h6l8n0wz355nhz35prtufpjsq2", "account_number"=>9, "sequence"=>302}

$client->deleteAll($gasInfo);

$client->create('kv', 'value', $gasInfo);

$client->txRead('kv', $gasInfo);
# value

$client->update('kv', 'new_value', $gasInfo);

$client->txRead('kv', $gasInfo);
echo var_dump($val);
# new_value

$client->txKeys($gasInfo);
# ['key']

$client->delete('kv');