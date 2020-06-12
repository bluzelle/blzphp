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

    private const MAX_RETRIES = 10;
    private const RETRY_INTERVAL = 1;

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

        return $this->broadcastTrasnaction($method, 'txs', $txn);
    }

    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    private function broadcastTrasnaction($method, $endpoint, $txn)
    {
        $url = $this->endpoint . '/' .$endpoint;

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

        $res = $this->request('POST', $url, [
            'json' => [
                'mode' => 'block',
                'tx' => $txn
            ]
        ]);

        if (isset($res['code'])) {
            $rawLog = $res['raw_log'];

            if (strpos($rawLog, 'signature verification failed') == true) {
                $this->updateAccountSequence($method, $endpoint, $txn, self::MAX_RETRIES);
            } else {
                throw new \Exception($this->extractErrorMessage($rawLog));
            }
        } else {
            $this->accountInfo['sequence']++;

            if (isset($res['data'])) {
                return Utils::jsonDecode(Utils::decodeHex($res['data']));
            }
        }
    }

    private function updateAccountSequence($method, $endpoint, $txn, $retries)
    {
        if ($retries) {
            sleep(self::RETRY_INTERVAL);

            $changed = $this->accountSequenceChanged();

            if ($changed) {
                $this->broadcastTrasnaction($method, $endpoint, $txn);
            } else {
                $this->updateAccountSequence($method, $endpoint, $txn, $retries - 1);
            }
        } else {
            throw new \Exception('Invalid chain id');
        }
    }

    private function accountSequenceChanged()
    {
        $accountInfo = $this->account();

        if (((int) $accountInfo['sequence']) !== ((int) $this->accountInfo['sequence'])) {
            $this->accountInfo['sequence'] = $accountInfo['sequence'];
            return true;
        }

        return false;
    }

    private function request($method, $url, $data = []): array
    {
        $res = $this->httpClient->request($method, $url, $data);
        return Utils::jsonDecode($res->getBody()->getContents());
    }

    private function validateAddress()
    {
        $retrievedAddress = Utils::getAddressFromPublicKey($this->getPublicKey());

        if ($this->address !== $retrievedAddress) {
            throw new Exception\AddressValidationFailedException();
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

        return Utils::base64Encode(
            $this->generateSignature(Utils::sanitizeString(Utils::jsonEncode($data)))
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
        $maxGas = isset($gasInfo['max_gas']) ? $gasInfo['max_gas'] : null;
        $maxFee = isset($gasInfo['max_fee']) ? $gasInfo['max_fee'] : null;
        $gasPrice = isset($gasInfo['gas_price']) ? $gasInfo['gas_price'] : null;
        $gas = (int) $txn['fee']['gas'];

        if (isset($maxGas) && $maxGas != 0 && $gas > $maxGas) {
            $gas = $maxGas;
        }

        if (!is_null($maxFee) && $maxFee != 0) {
            $txn['fee']['amount'] = [[
                'denom' => 'ubnt',
                'amount' => (string) $maxFee
            ]];
        } elseif (!is_null($gasPrice) && $gasPrice != 0) {
            $txn['fee']['amount'] = [[
                'denom' => 'ubnt',
                'amount' => (string) ($gas * $gasPrice)
            ]];
        }

        $txn['fee']['gas'] = (string) $gas;

        return $txn;
    }

    private function extractErrorMessage(string $msg): string {
        $offset1 = strpos($msg, ': ');

        if ($offset1 == false) {
            return $msg;
        }

        $prefix = Utils::substring($msg, 0, $offset1);

        switch ($prefix) {
            case 'insufficient fee':
                return Utils::substring($msg, $offset1 + 2);
            default:
                break;
        }

        $offset2 = strpos($msg, ':', $offset1 + 1);
        return Utils::substring($msg, $offset1 + 2, $offset2);
    }
}
