<?php

declare(strict_types=1);

namespace Bluzelle;

use \GuzzleHttp\Client as HttpClient;
use \Elliptic\EC;

class Cosmos
{
    private $endpoint;
    private $chainId;
    private $httpClient;
    private $accountInfo;
    private $key;

    public function __construct(string $address, string $mnemoic, string $endpoint, string $chainId)
    {
        $this->address = $address;
        $this->endpoint = $endpoint;
        $this->chainId = $chainId;
        $this->accountInfo = [];
        $this->httpClient = new HttpClient();
        $this->key = Utils::getECKey($mnemoic);

        $this->validateAddress();
        $this->setAccountDetails();
    }

    public function query(string $endpoint)
    {
        return $this->request('GET', $this->endpoint . '/' . $endpoint);
    }

    public function sendTransaction(string $method, string $endpoint, array $params, array $gasInfo)
    {
        $url = $this->endpoint . '/' . $endpoint;

        $txn = $this->request($method, $url, [ 
            'json' => $params
        ])['value'];

        $txn = $this->updateGas($txn, $gasInfo);

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

        return $this->broadcastTrasnaction($method, 'txs', $txn);
    }

    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    private function broadcastTrasnaction($method, $endpoint, $txn)
    {
        $url = $this->endpoint . '/' .$endpoint;

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

    private function request($method, $url, $data = []): array
    {
        $res = $this->httpClient->request($method, $url, $data);
        return Utils::jsonDecode($res->getBody()->getContents());
    }

    private function validateAddress()
    {
        $retrievedAddress = Utils::getAddressFromPublicKey($this->getPublicKey());

        if ($this->address !== $retrievedAddress)
            throw new Exception\AddressValidationFailedException();
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

        return Utils::base64Encode(
            $this->generateSignature(Utils::jsonEncode($data))
        );
    }

    private function generateSignature(string $data): string
    {
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate($this->getPrivateKey());

        $hash = Utils::sha256Digest($data);
        $sig = $key->sign($hash, 'hex', [ 'canonical' => true ]);

        $bArr = array_merge(
            $sig->r->toArray('be', 32),
            $sig->s->toArray('be', 32)
        );

        return join(array_map('chr', $bArr));
    }

    private function setAccountDetails()
    {
        $data = $this->account();

        $this->accountInfo['account_number'] = $data['account_number'];
        $this->accountInfo['sequence'] = $data['sequence'];
    }

    private function account()
    {
        $url = 'auth/accounts/' . $this->address;
        return $this->query($url)['result']['value'];
    }

    private function getPrivateKey(): string
    {
        return $this->key->getPrivateKey()->getHex();
    }

    private function getPublickey(): string
    {
        return $this->key->getPublickey()->getHex();
    }

    private function updateGas(array $txn, array $gasInfo): array
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
}