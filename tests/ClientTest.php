<?php

declare(strict_types=1);

namespace Bluzelle\Test;

use \Bluzelle\Client;
use \GuzzleHttp\Client as HttpClient;
use \GuzzleHttp\Handler\MockHandler;
use \GuzzleHttp\HandlerStack;
use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Middleware;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    
    protected $client;
    private $txSkeleton = [
        'type' => 'cosmos-sdk/StdTx',
        'value' => [
          'msg' => [
            [
              'type' => 'crud/create',
              'value' => [
                'UUID' => '',
                'Key' => '',
                'Value' => '',
                'Owner' => ''
              ]
            ]
          ],
          'fee' => [
            'amount' => [],
            'gas' => '100000'
          ],
          'signatures' => null,
          'memo' => ''
        ]
    ];
    
    protected function setUp(): void
    {
        $this->client = new Client(
            'bluzelle1upsfjftremwgxz3gfy0wf3xgvwpymqx754ssu9',
            'around buzz diagram captain obtain detail salon mango muffin brother morning jeans display attend knife carry green dwarf vendor hungry fan route pumpkin car',
            'http://testnet.public.bluzelle.com:1317',
            'bluzelle',
            '20fc19d4-7c9d-4b5c-9578-8cedd756e0ea'
        );
    }

    public function testCreate()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode([ 'data' => '' ]))
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);
        
        $httpClient = new HttpClient(['handler' => $handler]);
        
        $this->client->setHttpClient($httpClient);

        $this->client->create(
            'key',
            'value',
            ['max_fee' => 4000000]
        );

        $firstRequest = $container[0];
        $secondRequest = $container[1];

        $method = $firstRequest['request']->getMethod();
        $crudPath = $firstRequest['request']->getUri()->getPath();

        $txPath = $secondRequest['request']->getUri()->getPath();

        $this->assertEquals(\count($container), 2);
        $this->assertEquals($method, 'POST');
        $this->assertEquals($crudPath, '/crud/create');
        $this->assertEquals($txPath, '/txs');
    }

    public function testUpdate()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode([ 'data' => '' ]))
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);
        
        $httpClient = new HttpClient(['handler' => $handler]);
        
        $this->client->setHttpClient($httpClient);

        $this->client->update(
            'key',
            'value',
            ['max_fee' => 4000000]
        );

        $firstRequest = $container[0];
        $secondRequest = $container[1];

        $method = $firstRequest['request']->getMethod();
        $crudPath = $firstRequest['request']->getUri()->getPath();

        $txPath = $secondRequest['request']->getUri()->getPath();

        $this->assertEquals(\count($container), 2);
        $this->assertEquals($method, 'POST');
        $this->assertEquals($crudPath, '/crud/update');
        $this->assertEquals($txPath, '/txs');
    }

    public function testRead()
    {
        $expectedRes = [
            'result' => [
                'value' => 'value'
            ]
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->read('key');

        $this->assertEquals($res, 'value');
    }

    public function testTxRead()
    {
        $expectedRes = [
            'data' => \bin2hex(\json_encode([ 'value' => 'value' ]))
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->txRead('key', [ 'max_fee' => 4000000 ]);

        $this->assertEquals($res, 'value');
    }

    public function testDelete()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode([ 'data' => '' ]))
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);
        
        $httpClient = new HttpClient(['handler' => $handler]);
        
        $this->client->setHttpClient($httpClient);

        $this->client->delete(
            'key',
            ['max_fee' => 4000000]
        );

        $firstRequest = $container[0];
        $secondRequest = $container[1];

        $method = $firstRequest['request']->getMethod();
        $crudPath = $firstRequest['request']->getUri()->getPath();

        $txPath = $secondRequest['request']->getUri()->getPath();

        $this->assertEquals(\count($container), 2);
        $this->assertEquals($method, 'DELETE');
        $this->assertEquals($crudPath, '/crud/delete');
        $this->assertEquals($txPath, '/txs');
    }

    public function testHas()
    {
        $expectedRes = [
            'result' => [
                'has' => true
            ]
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->has('key');

        $this->assertEquals($res, true);
    }

    public function testTxHas()
    {
        $expectedRes = [
            'data' => \bin2hex(\json_encode([ 'has' => true ]))
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->txHas('key', [ 'max_fee' => 4000000 ]);

        $this->assertEquals($res, true);
    }

    public function keys()
    {
        $keys = [ 'key1', 'key2', 'key3' ];

        $expectedRes = [
            'result' => [
                'keys' => $keys
            ]
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->keys();

        $this->assertEquals($res, $keys);
    }

    public function txKeys()
    {
        $keys = [ 'key1', 'key2', 'key3' ];

        $expectedRes = [
            'data' => \bin2hex(\json_encode([ 'keys' => $keys ]))
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->txKeys([ 'max_fee' => 4000000 ]);

        $this->assertEquals($res, $keys);
    }

    public function testRename()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode([ 'data' => '' ]))
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);
        
        $httpClient = new HttpClient(['handler' => $handler]);
        
        $this->client->setHttpClient($httpClient);

        $this->client->rename(
            'key',
            'new_key',
            ['max_fee' => 4000000]
        );

        $firstRequest = $container[0];
        $secondRequest = $container[1];

        $method = $firstRequest['request']->getMethod();
        $crudPath = $firstRequest['request']->getUri()->getPath();

        $txPath = $secondRequest['request']->getUri()->getPath();

        $this->assertEquals(\count($container), 2);
        $this->assertEquals($method, 'POST');
        $this->assertEquals($crudPath, '/crud/rename');
        $this->assertEquals($txPath, '/txs');
    }

    public function testCount()
    {

        $expectedRes = [
            'result' => [
                'count' => 10
            ]
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->count();

        $this->assertEquals($res, 10);
    }

    public function testTxCount()
    {
        $expectedRes = [
            'data' => \bin2hex(\json_encode([ 'count' => 10 ]))
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->txCount([ 'max_fee' => 4000000 ]);

        $this->assertEquals($res, 10);
    }

    public function testDeleteAll()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode([ 'data' => '' ]))
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);
        
        $httpClient = new HttpClient(['handler' => $handler]);
        
        $this->client->setHttpClient($httpClient);

        $this->client->deleteAll(
            ['max_fee' => 4000000]
        );

        $firstRequest = $container[0];
        $secondRequest = $container[1];

        $method = $firstRequest['request']->getMethod();
        $crudPath = $firstRequest['request']->getUri()->getPath();

        $txPath = $secondRequest['request']->getUri()->getPath();

        $this->assertEquals(\count($container), 2);
        $this->assertEquals($method, 'POST');
        $this->assertEquals($crudPath, '/crud/deleteall');
        $this->assertEquals($txPath, '/txs');
    }

    public function testKeyValues()
    {
        $kv = [
            'keyvalues' => [
                [ 'key' => 'key1', 'value' => 'value1' ],
                [ 'key' => 'key2', 'value' => 'value2' ]
            ]
        ];

        $expectedRes = [
            'result' => $kv
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->keyValues('key');

        $this->assertEquals($res, $kv['keyvalues']);
    }

    public function testTxKeyValues()
    {
        $kv = [
            'keyvalues' => [
                [ 'key' => 'key1', 'value' => 'value1' ],
                [ 'key' => 'key2', 'value' => 'value2' ]
            ]
        ];

        $expectedRes = [
            'data' => \bin2hex(\json_encode($kv))
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->txKeyValues([ 'max_fee' => 4000000 ]);

        $this->assertEquals($res, $kv['keyvalues']);
    }

    public function testMultiUpdate()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode([ 'data' => '' ]))
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);
        
        $httpClient = new HttpClient(['handler' => $handler]);
        
        $this->client->setHttpClient($httpClient);

        $this->client->multiUpdate(
            [
                [ 'key' => 'key1', 'value' => 'value1' ],
                [ 'key' => 'key2', 'value' => 'value2' ]
            ],
            ['max_fee' => 4000000]
        );

        $firstRequest = $container[0];
        $secondRequest = $container[1];

        $method = $firstRequest['request']->getMethod();
        $crudPath = $firstRequest['request']->getUri()->getPath();

        $txPath = $secondRequest['request']->getUri()->getPath();

        $this->assertEquals(\count($container), 2);
        $this->assertEquals($method, 'POST');
        $this->assertEquals($crudPath, '/crud/multiupdate');
        $this->assertEquals($txPath, '/txs');
    }

    public function testGetLease()
    {
        $expectedRes = [
            'result' => [
                'lease' => '20'
            ]
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->getLease('key');

        $this->assertEquals($res, 100);
    }

    public function testTxGetLease()
    {
        $expectedRes = [
            'data' => \bin2hex(\json_encode([
                'lease' => '20'
            ]))
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->txGetLease('key', [ 'max_fee' => 4000000 ]);

        $this->assertEquals($res, 100);
    }

    public function testRenewLease()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode([ 'data' => '' ]))
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);
        
        $httpClient = new HttpClient(['handler' => $handler]);
        
        $this->client->setHttpClient($httpClient);

        $this->client->renewLease(
            'key',
            [ 'days' => '2', 'hours' => '3', 'minutes' => '20', 'seconds' => '30' ],
            ['max_fee' => 4000000]
        );

        $firstRequest = $container[0];
        $secondRequest = $container[1];

        $method = $firstRequest['request']->getMethod();
        $crudPath = $firstRequest['request']->getUri()->getPath();

        $txPath = $secondRequest['request']->getUri()->getPath();

        $this->assertEquals(\count($container), 2);
        $this->assertEquals($method, 'POST');
        $this->assertEquals($crudPath, '/crud/renewlease');
        $this->assertEquals($txPath, '/txs');
    }

    public function testRenewLeaseAll()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode([ 'data' => '' ]))
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);
        
        $httpClient = new HttpClient(['handler' => $handler]);
        
        $this->client->setHttpClient($httpClient);

        $this->client->renewLeaseAll(
            [ 'days' => '2', 'hours' => '3', 'minutes' => '20', 'seconds' => '30' ],
            ['max_fee' => 4000000]
        );

        $firstRequest = $container[0];
        $secondRequest = $container[1];

        $method = $firstRequest['request']->getMethod();
        $crudPath = $firstRequest['request']->getUri()->getPath();

        $txPath = $secondRequest['request']->getUri()->getPath();

        $this->assertEquals(\count($container), 2);
        $this->assertEquals($method, 'POST');
        $this->assertEquals($crudPath, '/crud/renewleaseall');
        $this->assertEquals($txPath, '/txs');
    }

    public function testGetNShortestLease()
    {
        $expectedRes = [
            'result' => [
                'keyleases' => [
                    [
                        'key' => 'key1',
                        'lease' => 100
                    ],
                    [
                        'key' => 'key2',
                        'lease' => 200
                    ]
                ]
            ]
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->getNShortestLease(10);

        $this->assertEquals($res, $expectedRes['result']['keyleases']);
    }

    public function testTxGetNShortestLease()
    {
        $expectedRes = [
            'data' => \bin2hex(\json_encode([
                'keyleases' => [
                    [
                        'key' => 'key1',
                        'lease' => 100
                    ],
                    [
                        'key' => 'key2',
                        'lease' => 200
                    ]
                ]
            ]))
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($this->txSkeleton)),
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);
        
        $this->client->setHttpClient($httpClient);

        $res = $this->client->txGetNShortestLease(10, ['max_fee' => 400000]);

        $this->assertEquals($res, \json_decode(\hex2bin($expectedRes['data']), true)['keyleases']);
    }

    public function testShouldReturnAccountDetails()
    {
        $expectedRes = [
            'result' => [
                'value' => [
                    'account_number' => '0',
                    'sequence' => '1'
                ]
            ]
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->account();

        $this->assertEquals($res, $expectedRes['result']['value']);
    }

    public function testShouldReturnVersion()
    {
        $expectedRes = [
            "application_version" => [
              "name" => 'BluzelleService',
              "server_name" => 'blzd',
              "client_name" => 'blzcli',
              "version" => '0.0.0-39-g8895e3e',
              "commit" => '8895e3edf0a3ede0f6ed30f2224930e8faa1236e',
              "build_tags" => 'ledger,faucet,cosmos-sdk v0.38.1',
              "go" => 'go version go1.13.4 linux/amd64'
            ]
        ];

        $mock = new MockHandler([
            new Response(200, [], \json_encode($expectedRes))
        ]);
        $handler = HandlerStack::create($mock);
        $httpClient = new HttpClient(['handler' => $handler]);

        $this->client->setHttpClient($httpClient);

        $res = $this->client->version();

        $this->assertEquals($res, $expectedRes['application_version']['version']);
    }
}
