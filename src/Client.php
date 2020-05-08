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
    private $chainId;
    private $uuid;
    private $gasInfo;
    private $accountInfo;
    private $httpClient;


    /**
     * Create a new Bluzelle Client instance
     */
    public function __construct(string $address, string $mnemonic, $endpoint = null, $chainId = 'bluzelle', $uuid = null)
    {
        $this->address = $address;
        $this->mnemonic = $mnemonic;
        $this->chainId = $chainId;
        
        $this->endpoint = isset($endpoint) ? $endpoint : 'http://localhost:1317';
        $this->uuid = isset($uuid) ? $uuid : $this->address;

        $this->httpClient = new \GuzzleHttp\Client();
        $this->key = Utils::getECKey($this->mnemonic);

        $this->validateAddress();
        $this->setAccountDetails();
    }

    public function create(string $key, string $value, array $gasInfo, array $leaseInfo = null): void
    {
        $lease = Utils::convertLease($leaseInfo);
        $this->validateLease($lease);

        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/create',
            $this->buildParams([ 'Key' => $key, 'Value' => $value, 'Lease' => (string) $lease ]),
            $gasInfo
        );
    }

    public function update(string $key, string $value, array $gasInfo, array $leaseInfo = null): void
    {
        $lease = Utils::convertLease($leaseInfo);
        $this->validateLease($lease);

        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/update',
            $this->buildParams([ 'Key' => $key, 'Value' => $value, 'Lease' => (string) $lease ]),
            $gasInfo
        );
    }
    

    public function read(string $key, $prove = false): string
    {
        $path = $prove ? 'pread' : 'read';
        $url = self::APP_SERVICE . "/" . $path . "/" . $this->uuid  . "/" . $key;
        return $this->query($url)['result']['value'];
    }

    public function txRead(string $key, array $gasInfo): string
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/read',
            $this->buildParams([ 'Key' => $key ]),
            $gasInfo
        )['value'];
    }

    public function delete(string $key, array $gasInfo): void
    {
        $this->sendTransaction(
            'DELETE',
            self::APP_SERVICE . '/delete',
            $this->buildParams([ 'Key' => $key ]),
            $gasInfo
        );
    }

    public function has(string $key): bool
    {
        $url = self::APP_SERVICE . '/has/' . $this->uuid . '/' . $key;
        return $this->query($url)['result']['has'];
    }

    public function txHas(string $key, array $gasInfo): bool
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/has',
            $this->buildParams([ 'key' => $key ]),
            $gasInfo
        )['has'];
    }

    public function keys(): array
    {
        $url = self::APP_SERVICE . '/keys/' . $this->uuid;
        return $this->query($url)['result']['keys'];
    }

    public function txkeys(array $gasInfo): array
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/keys',
            $this->buildParams([]),
            $gasInfo
        )['keys'];
    }

    public function rename(string $key, string $newKey, array $gasInfo): void
    {
        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/rename',
            $this->buildParams([ 'Key' => $key, 'NewKey' => $newKey ]),
            $gasInfo
        );
    }

    public function count(): int
    {
        $url = self::APP_SERVICE . '/count/' . $this->uuid;
        return $this->query($url)['result']['count'];
    }

    public function txCount(array $gasInfo): int
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/count',
            $this->buildParams([]),
            $gasInfo
        )['count'];
    }

    public function deleteAll(array $gasInfo): void
    {
        $this->sendTransaction(
            'DELETE',
            self::APP_SERVICE . '/deleteall',
            $this->buildParams([]),
            $gasInfo
        );
    }

    public function keyValues(): array
    {
        $url = self::APP_SERVICE . '/keyvalues/' . $this->uuid;
        return $this->query($url)['result']['keyvalues'];
    }

    public function txKeyValues(array $gasInfo): array
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/keyvalues',
            $this->buildParams([]),
            $gasInfo
        )['keyvalues'];
    }

    public function multiUpdate(array $keyValues, array $gasInfo): void
    {
        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/multiupdate',
            $this->buildParams([ 'KeyValues' => $keyValues ]),
            $gasInfo
        );
    }

    public function getLease(string $key): string
    {
        $url = self::APP_SERVICE . '/getlease/' . $this->uuid . '/' . $key;
        $lease = (int) $this->query($url)['result']['lease'];
        return (string) ($lease * self::BLOCK_TIME_IN_SECONDS);
    }

    public function txGetLease(string $key, array $gasInfo): string
    {
        $lease = (int) $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/getlease',
            $this->buildParams([ 'Key' => $key ]),
            $gasInfo
        )['lease'];

        return (string) ($lease * self::BLOCK_TIME_IN_SECONDS);
    }

    public function renewLease(string $key, array $leaseInfo, array $gasInfo): void
    {
        $lease = Utils::convertLease($leaseInfo);
        $this->validateLease($lease);

        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/renewlease',
            $this->buildParams([ 'Key' => $key, 'Lease' => (string) $lease ]),
            $gasInfo
        );
    }

    public function renewLeaseAll(array $leaseInfo, array $gasInfo): void
    {
        $lease = Utils::convertLease($leaseInfo);
        $this->validateLease($lease);

        $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/renewleaseall',
            $this->buildParams([ 'Lease' => (string) $lease ]),
            $gasInfo
        );
    }

    public function getNShortestLease(int $n): array
    {
        $url = self::APP_SERVICE . '/getnshortestlease/' . $this->uuid . '/' . $n;
        return $this->query($url)['result']['keyleases'];
    }

    public function txGetNShortestLease(int $n, array $gasInfo): array
    {
        return $this->sendTransaction(
            'POST',
            self::APP_SERVICE . '/getnshortestlease',
            $this->buildParams([ 'N' => (string) $n ]),
            $gasInfo
        )['keyleases'];
    }

    public function account(): array
    {
        $url = 'auth/accounts/' . $this->address;
        return $this->query($url)['result']['value'];
    }

    public function version(): string
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
            throw new Exception\AddressValidationFailedException();
        }
    }

    private function validateLease(int $blocks)
    {
        if ($blocks < 0) throw new Exception\InvalidLeaseException();
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
