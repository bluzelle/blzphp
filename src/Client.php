<?php

declare(strict_types=1);

namespace Bluzelle;

class Client
{
    private const BLOCK_TIME_IN_SECONDS = 5;
    private const APP_SERVICE = 'crud';

    private $address;
    private $endpoint;
    private $chainId;
    private $uuid;
    private $httpClient;
    private $cosmos;


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

        $this->cosmos = new Cosmos($address, $mnemonic, $this->endpoint, $this->chainId);
    }

    public function create($key, $value, array $gasInfo, array $leaseInfo = null): void
    {
        if (!\is_string($key)) {
            throw new \Exception('Key must be a string');
        }
        
        if (!\is_string($value)) {
            throw new \Exception('Value must be a string');
        }
        
        if (empty($key)) {
            throw new \Exception('Key cannot be empty');
        }
        
        if (\strpos($key, '/') == true) {
            throw new \Exception('Key cannot contain a slash');
        }
        
        $lease = Utils::convertLease($leaseInfo);
        $this->validateLease($lease);

        $this->cosmos->sendTransaction(
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

        $this->cosmos->sendTransaction(
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
        return $this->cosmos->query($url)['result']['value'];
    }

    public function txRead(string $key, array $gasInfo): string
    {
        return $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/read',
            $this->buildParams([ 'Key' => $key ]),
            $gasInfo
        )['value'];
    }

    public function delete(string $key, array $gasInfo): void
    {
        $this->cosmos->sendTransaction(
            'DELETE',
            self::APP_SERVICE . '/delete',
            $this->buildParams([ 'Key' => $key ]),
            $gasInfo
        );
    }

    public function has(string $key): bool
    {
        $url = self::APP_SERVICE . '/has/' . $this->uuid . '/' . $key;
        return $this->cosmos->query($url)['result']['has'];
    }

    public function txHas(string $key, array $gasInfo): bool
    {
        return $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/has',
            $this->buildParams([ 'Key' => $key ]),
            $gasInfo
        )['has'];
    }

    public function keys(): array
    {
        $url = self::APP_SERVICE . '/keys/' . $this->uuid;
        return $this->cosmos->query($url)['result']['keys'];
    }

    public function txkeys(array $gasInfo): array
    {
        return $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/keys',
            $this->buildParams([]),
            $gasInfo
        )['keys'];
    }

    public function rename(string $key, string $newKey, array $gasInfo): void
    {
        $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/rename',
            $this->buildParams([ 'Key' => $key, 'NewKey' => $newKey ]),
            $gasInfo
        );
    }

    public function count(): int
    {
        $url = self::APP_SERVICE . '/count/' . $this->uuid;
        return (int) $this->cosmos->query($url)['result']['count'];
    }

    public function txCount(array $gasInfo): int
    {
        return (int) $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/count',
            $this->buildParams([]),
            $gasInfo
        )['count'];
    }

    public function deleteAll(array $gasInfo): void
    {
        $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/deleteall',
            $this->buildParams([]),
            $gasInfo
        );
    }

    public function keyValues(): array
    {
        $url = self::APP_SERVICE . '/keyvalues/' . $this->uuid;
        return $this->cosmos->query($url)['result']['keyvalues'];
    }

    public function txKeyValues(array $gasInfo): array
    {
        return $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/keyvalues',
            $this->buildParams([]),
            $gasInfo
        )['keyvalues'];
    }

    public function multiUpdate(array $keyValues, array $gasInfo): void
    {
        $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/multiupdate',
            $this->buildParams([ 'KeyValues' => $keyValues ]),
            $gasInfo
        );
    }

    public function getLease(string $key): int
    {
        $url = self::APP_SERVICE . '/getlease/' . $this->uuid . '/' . $key;
        $lease = (int) $this->cosmos->query($url)['result']['lease'];
        return $lease * self::BLOCK_TIME_IN_SECONDS;
    }

    public function txGetLease(string $key, array $gasInfo): int
    {
        $lease = (int) $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/getlease',
            $this->buildParams([ 'Key' => $key ]),
            $gasInfo
        )['lease'];

        return $lease * self::BLOCK_TIME_IN_SECONDS;
    }

    public function renewLease(string $key, array $gasInfo, array $leaseInfo = null): void
    {
        $lease = Utils::convertLease($leaseInfo);
        $this->validateLease($lease);

        $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/renewlease',
            $this->buildParams([ 'Key' => $key, 'Lease' => (string) $lease ]),
            $gasInfo
        );
    }

    public function renewLeaseAll(array $gasInfo, array $leaseInfo = null): void
    {
        $lease = Utils::convertLease($leaseInfo);
        $this->validateLease($lease);

        $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/renewleaseall',
            $this->buildParams([ 'Lease' => (string) $lease ]),
            $gasInfo
        );
    }

    public function getNShortestLeases(int $n): array
    {
        $url = self::APP_SERVICE . '/getnshortestleases/' . $this->uuid . '/' . $n;
        $leases = $this->cosmos->query($url)['result']['keyleases'];
        
        return array_map(function ($elem) {
            return [ 
                'key' => $elem['key'], 
                'lease' => $elem['lease'] * self::BLOCK_TIME_IN_SECONDS 
            ];
        }, $leases);
    }

    public function txGetNShortestLeases(int $n, array $gasInfo): array
    {
        $leases = $this->cosmos->sendTransaction(
            'POST',
            self::APP_SERVICE . '/getnshortestleases',
            $this->buildParams([ 'N' => (string) $n ]),
            $gasInfo
        )['keyleases'];

        return array_map(function ($elem) {
            return [ 
                'key' => $elem['key'], 
                'lease' => $elem['lease'] * self::BLOCK_TIME_IN_SECONDS 
            ];
        }, $leases);
    }

    public function account(): array
    {
        $url = 'auth/accounts/' . $this->address;
        return $this->cosmos->query($url)['result']['value'];
    }

    public function version(): string
    {
        return $this->cosmos->query('node_info')['application_version']['version'];
    }

    public function setHttpClient($httpClient)
    {
        $this->cosmos->setHttpClient($httpClient);
    }

    private function validateLease(int $blocks)
    {
        if ($blocks < 0) {
            throw new Exception\InvalidLeaseException();
        }
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
