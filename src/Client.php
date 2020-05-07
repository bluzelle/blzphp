<?php

declare(strict_types=1);

namespace Bluzelle\Bluzelle;

use \Elliptic\EC;

class Client
{
    private const BLOCK_TIME_IN_SECONDS = 5;
    private const APP_SERVICE = 'crud';

    private $address;
    private $mnemonic;
    private $endpoint;
    private $chainId = 'bluzelle';
    private $uuid;
    private $gasInfo;
    private $accountInfo;
    private $httpClient;


    /**
     * Create a new Bluzelle Client instance
     */
    public function __construct($address, $mnemonic, $endpoint, $chainId, $uuid)
    {
        $this->address = $address;
        $this->mnemonic = $mnemonic;
        $this->endpoint = $endpoint;
        $this->chainId = $chainId;
        $this->uuid = $uuid;

        $this->httpClient = new \GuzzleHttp\Client();
        $this->key = Utils::getECKey($this->mnemonic);

        $this->validateAddress();
        $this->setAccountDetails();
    }

    public function create($key, $value, $gasInfo, $leaseInfo = null)
    {
        $lease = Utils::convertLease($leaseInfo);

        // TODO: validate block

        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/create',
            $this->buildParams([ 'Key' => $key, 'Value' => $value, 'Lease' => $lease ]),
            $gasInfo
        );
    }

    public function update($key, $value, $gasInfo, $leaseInfo = null)
    {
        $lease = Utils::convertLease($leaseInfo);

        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/update',
            $this->buildParams([ 'Key' => $key, 'Value' => $value, 'Lease' => $lease ]),
            $gasInfo
        );
    }
    

    public function read($key, $prove = false)
    {
        $path = $prove ? 'pread' : 'read';
        $url = self::APP_SERVICE . "/" . $path . "/" . $this->uuid  . "/" . $key;
        return $this->query($url)['result']['value'];
    }

    public function txRead($key, $gasInfo)
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/read',
            $this->buildParams([ 'Key' => $key ]),
            $gasInfo
        )['value'];
    }

    public function delete($key, $gasInfo)
    {
        $this->sendTransaction(
            'DELETE',
            self::APP_SERVICE . '/delete',
            $this->buildParams([ 'Key' => $key ]),
            $gasInfo
        );
    }

    public function has($key)
    {
        $url = self::APP_SERVICE . '/has/' . $this->uuid . '/' . $key;
        return $this->query($url)['result']['has'];
    }

    public function txHas($key, $gasInfo)
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/has',
            $this->buildParams([ 'key' => $key ]),
            $gasInfo
        )['has'];
    }

    public function keys()
    {
        $url = self::APP_SERVICE . '/keys/' . $this->uuid;
        return $this->query($url)['result']['keys'];
    }

    public function txkeys($gasInfo)
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/keys',
            $this->buildParams([]),
            $gasInfo
        )['keys'];
    }

    public function rename($key, $newKey, $gasInfo)
    {
        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/rename',
            $this->buildParams([ 'Key' => $key, 'NewKey' => $newKey ]),
            $gasInfo
        );
    }

    public function count()
    {
        $url = self::APP_SERVICE . '/count/' . $this->uuid;
        return $this->query($url)['result']['count'];
    }

    public function txCount($gasInfo)
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/count',
            $this->buildParams([]),
            $gasInfo
        )['count'];
    }

    public function deleteAll($gasInfo)
    {
        return $this->sendTransaction(
            'DELETE',
            self::APP_SERVICE . '/deleteall',
            $this->buildParams([]),
            $gasInfo
        );
    }

    public function keyValues()
    {
        $url = self::APP_SERVICE . '/keyvalues/' . $this->uuid;
        return $this->query($url)['result']['keyvalues'];
    }

    public function txKeyValues($gasInfo)
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/keyvalues',
            $this->buildParams([]),
            $gasInfo
        )['keyvalues'];
    }

    public function multiUpdate($keyValues, $gasInfo)
    {
        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/multiupdate',
            $this->buildParams([ 'KeyValues' => $keyValues ]),
            $gasInfo
        );
    }

    public function getLease($key)
    {
        $url = self::APP_SERVICE . '/getlease/' . $this->uuid . '/' . $key;
        $lease = (int) $this->query($url)['result']['lease'];
        return $lease * self::BLOCK_TIME_IN_SECONDS;
    }

    public function txGetLease($key, $gasInfo)
    {
        $lease = (int) $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/getlease',
            $this->buildParams([ 'Key' => $key ]),
            $gasInfo
        )['lease'];

        return $lease * self::BLOCK_TIME_IN_SECONDS;
    }

    public function renewLease($key, $leaseInfo, $gasInfo)
    {
        $lease = Utils::convertLease($leaseInfo);

        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/renewlease',
            $this->buildParams([ 'Key' => $key, 'Lease' => $lease ]),
            $gasInfo
        );
    }

    public function renewLeaseAll($leaseInfo, $gasInfo)
    {
        $lease = Utils::convertLease($leaseInfo);

        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/renewleaseall',
            $this->buildParams([ 'Lease' => $lease ]),
            $gasInfo
        );
    }

    public function getNShortestLease($n)
    {
        $url = self::APP_SERVICE . '/getnshortestlease/' . $this->uuid . '/' . $n;
        return $this->query($url)['result']['keyleases'];
    }

    public function txGetNShortestLease($n, $gasInfo)
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/getnshortestlease',
            $this->buildParams([ 'N' => (string) $n ]),
            $gasInfo
        )['keyleases'];
    }

    public function account()
    {
        $url = 'auth/accounts/' . $this->address;
        return $this->query($url)['result']['value'];
    }

    public function version()
    {
        return $this->query('node_info')['application_version']['version'];
    }

    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    private function setAccountDetails()
    {
        $data = $this->account();

        $this->accountInfo['account_number'] = $data['account_number'];
        $this->accountInfo['sequence'] = $data['sequence'];
    }

    private function validateAddress()
    {
        $retrievedAddress = Utils::getAddressFromPublicKey($this->getPublicKey());

        if ($this->address !== $retrievedAddress)
        {
            throw new \Exception('Address verification failed');
        }
    }

    private function request($method, $url, $data = [])
    {
        $res = $this->httpClient->request($method, $url, $data);
        return Utils::jsonDecode($res->getBody()->getContents());
    }

    private function query($endpoint)
    {
        return $this->request('GET', $this->endpoint . '/' . $endpoint);
    }

    private function sendTransaction($method, $endpoint, $params, $gasInfo)
    {
        $url = $this->endpoint . '/' . $endpoint;

        $txn = $this->request($method, $url, [ 
            'json' => $params
        ])['value'];

        $txn = $this->updateGasAndFee($txn, $gasInfo);

        $txn['memo'] = Utils::makeRandomString();

        Utils::ksortRecursive($txn);

        $txn['signatures'] = [[
            'account_number' => (string) $this->accountInfo['account_number'],
            'pub_key' => [
                'type' => 'tendermint/PubKeySecp256k1',
                'value' => Utils::base64Encode(Utils::decodeHex($this->getPublicKey()))
            ],
            'sequence' => (string) $this->accountInfo['sequence'],
            'signature' => $this->signTransaction($txn)
        ]];

        return $this->broadcastTransaction($method, 'txs', $txn);
    }

    private function broadcastTransaction($method,  $endpoint, $txn)
    {
        $url = $this->endpoint . '/' . $endpoint;

        $res = $this->request($method, $url, [
            'json' => [
                'mode' => 'block',
                'tx' => $txn
            ]
        ]);

        if (isset($res['code'])) {
            throw new \Exception($res['raw_log']);
        } else {
            return Utils::jsonDecode(Utils::decodeHex($res['data']));
        }
    }

    private function signTransaction($txn)
    {
        $data = [
            'account_number' => (string) $this->accountInfo['account_number'],
            'chain_id' => $this->chainId,
            'fee' => $txn['fee'],
            'memo' => $txn['memo'],
            'msgs' => $txn['msg'],
            'sequence' => (string) $this->accountInfo['sequence']
        ];

        return Utils::base64Encode($this->signData(Utils::jsonEncode($data)));
    }

    private function signData($data)
    {
        $ec = new EC('secp256k1');

        $key = $ec->keyFromPrivate($this->getPrivateKey());
        
        $hash = Utils::sha256Digest($data);
        
        $sig = $key->sign($hash, 'hex', [ 'canonical' => true ]);

        $arr = array_merge($sig->r->toArray('be', 32), $sig->s->toArray('be', 32));

        return \join(\array_map('chr', $arr));
    }

    private function getPrivateKey()
    {
        return $this->key->getPrivateKey()->getHex();
    }

    private function getPublicKey()
    {
        return $this->key->getPublicKey()->getHex();
    }

    private function updateGasAndFee($txn, $gasInfo)
    {

        $gas = (int) $txn['fee']['gas'];
        $maxGasParam = null;
        $maxPriceParam = null;

        if (isset($gasInfo['max_fee'])) {
            $maxGasParam = (int) $gasInfo['max_fee'];
        }

        if (isset($gasInfo['gas_price'])) {
            $maxPriceParam = (int) $gasInfo['gas_price'];
        }

        
        if ($gas > $maxGasParam) {
            $txn['fee']['gas'] = $maxGasParam;
        }

        if ($maxGasParam) {
            $txn['fee']['amount'] = [[
                'denom' => 'ubnt',
                'amount' => (string) $maxGasParam 
            ]];
        } else if ($maxPriceParam) {
            $txn['fee']['amount'] = [[
                'denom' => 'ubnt',
                'amount' => (string) ($gas * $maxPriceParam)
            ]];
        }

        return $txn;
    }

    private function buildParams($params)
    {
        $baseParams = [
            'BaseReq' => [
                'chain_id' => $this->chainId,
                'from' => $this->address
            ],
            'Owner' => $this->address,
            'UUID' => $this->uuid
        ];
        
        return array_merge($baseParams, $params);
    }
}
