# blzphp

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

# Installation

You can install this library via Composer: composer require bluzelle/blzphp

# Getting Started

In general, you must initialize blzphp before calling any other functions. Do the following using your own configuration parameters as applicable to initialize:
```php
blz = new Bluzelle\Client('account', 'mnemonic', 'endpoint', 'chainId', 'uuid');
```  
This performs some initial checks, retrieves your account information, and returns an object through which you can call the rest of the API functions.

You may now use the functions described below to perform database operations, as well as retrieve account and status information.

# blzphp API documentation
Read below for detailed documentation on how to use the Bluzelle database service.

### new Bluzelle\Client\({...}\)

Configures the Bluzelle connection. Multiple clients can be created by creating new instances of this class.

```php
use Bluzelle\Client;

api = new Client(
    'bluzelle1xhz23a58mku7ch3hx8f9hrx6he6gyujq57y3kp',
    'volcano arrest ceiling physical concert sunset absent hungry tobacco canal census era pretty car code crunch inside behind afraid express giraffe reflect stadium luxury',
    "http://localhost:1317",
    "20fc19d4-7c9d-4b5c-9578-8cedd756e0ea",
    "bluzelleTestPublic-1"
);
```

| Argument | Description |
| :--- | :--- |
| **address** | The address of your Bluzelle account |
| **mnemonic** | The mnemonic of the private key for your Bluzelle account |
| endpoint | \(Optional\) The hostname and port of your rest server. Default: http://localhost:1317 |
| uuid | \(Optional\) Bluzelle uses `UUID`'s to identify distinct databases on a single swarm. We recommend using [Version 4 of the universally unique identifier](https://en.wikipedia.org/wiki/Universally_unique_identifier#Version_4_%28random%29). Defaults to the account address. |
| chain_id | \(Optional\) The chain id of your Bluzelle account. Default: bluzelle |


The calls below are methods of the instance created by instantiating the `Bluzelle::Swarm::Client` class.

## General Functions

### version\()

Retrieve the version of the Bluzelle service.

```php
api->version();
```

Returns a promise resolving to a string containing the version information, e.g.

```
0.0.0-39-g8895e3e
```

Throws an exception if a response is not received from the connection.


### account\()

Retrieve information about the currently active Bluzelle account.

```php
api->account();
```

Returns a promise resolving to a JSON object representing the account information, e.g.

Throws an exception if a response is not received from the connection.


## Database Functions

### create\($key, $value ,$gas_info[,  $lease_info]\)

Create a field in the database.

```php
api->create('mykey', '{ a: 13 }', ['gax_fee' => '400001'], ['days' => 100]);
```

| Argument | Description |
| :--- | :--- |
| key | The name of the key to create |
| value | The string value to set the key |
| gas_info | Object containing gas parameters (see above) |
| lease_info (optional) | Minimum time for key to remain in database (see above) |

Returns a promise resolving to nothing.

Throws an exception when a response is not received from the connection, the key already exists, or invalid value.

### read\($key, $prove\)

Retrieve the value of a key without consensus verification. Can optionally require the result to have a cryptographic proof (slower).

```php
value = api->read('mykey');
```

| Argument | Description |
| :--- | :--- |
| key | The key to retrieve |
| prove | A proof of the value is required from the network (requires 'config trust-node false' to be set) |

Returns a promise resolving the string value of the key.

Throws an exception when the key does not exist in the database.
Throws an exception when the prove is true and the result fails verification.

### txRead\($key, $gas_info\)

Retrieve the value of a key via a transaction (i.e. uses consensus).

```php
value = api->txRead('mykey', ['max_fee' => '400001']);
```

| Argument | Description |
| :--- | :--- |
| key | The key to retrieve |
| gas_info | Object containing gas parameters (see above) |

Returns a promise resolving the string value of the key.

Throws an exception when the key does not exist in the database.

### update\($key, $value , gas_info, lease_info\)

Update a field in the database.

```php
api->update('mykey', '{ a: 13 }', ['max_fee': '400001'], ['days' => 100]);
```

| Argument | Description |
| :--- | :--- |
| key | The name of the key to create |
| value | The string value to set the key |
| gas_info | Object containing gas parameters (see above) |
| lease_info (optional) | Positive or negative amount of time to alter the lease by. If not specified, the existing lease will not be changed. |

Returns a promise resolving to nothing.

Throws an exception when the key doesn't exist, or invalid value.

### delete\($key, $gas_info\)

Delete a field from the database.

```php
api->delete('mykey', ['max_fee' => '400001']);
```

| Argument | Description |
| :--- | :--- |
| key | The name of the key to delete |
| gas_info | Object containing gas parameters (see above) |

Returns a promise resolving to nothing.

Throws an exception when the key is not in the database.

### has\($key\)

Query to see if a key is in the database. This function bypasses the consensus and cryptography mechanisms in favor of speed.


```php
hasMyKey = api->has('mykey');
```

| Argument | Description |
| :--- | :--- |
| key | The name of the key to query |

Returns a promise resolving to a boolean value - `true` or `false`, representing whether the key is in the database.

### txHas\($key, $gas_info\)

Query to see if a key is in the database via a transaction (i.e. uses consensus).

```php
hasMyKey = api->txHas('mykey', ['gas_price' => 10]);
```

| Argument | Description |
| :--- | :--- |
| key | The name of the key to query |
| gas_info | Object containing gas parameters (see above) |

Returns a promise resolving to a boolean value - `true` or `false`, representing whether the key is in the database.

### keys\(\)

Retrieve a list of all keys. This function bypasses the consensus and cryptography mechanisms in favor of speed.

```php
keys = api->keys();
```

Returns a promise resolving to an array of strings. ex. `["key1", "key2", ...]`.

### txKeys\($gas_info\)

Retrieve a list of all keys via a transaction (i.e. uses consensus).

```php
keys = api->txKeys([ 'gas_price' => 10]);
```

| Argument | Description |
| :--- | :--- |
| gas_info | Object containing gas parameters (see above) |

Returns a promise resolving to an array of strings. ex. `["key1", "key2", ...]`.

### rename\($key, $new_key, $gas_info\)

Change the name of an existing key.

```php
api->rename('key', 'newkey', ['gas_price' => 10]);
```

| Argument | Description |
| :--- | :--- |
| key | The name of the key to rename |
| new_key | The new name for the key |
| gas_info | Object containing gas parameters (see above) |

Returns a promise resolving to nothing.

Throws an exception if the key doesn't exist.


| Argument | Description |
| :--- | :--- |
| key | The name of the key to query |

Returns a promise resolving to a boolean value - `true` or `false`, representing whether the key is in the database.

### count\(\)

Retrieve the number of keys in the current database/uuid. This function bypasses the consensus and cryptography mechanisms in favor of speed.

```php
number = api->count();
```

Returns a promise resolving to an integer value.

### txCount\($gas_info\)

Retrieve the number of keys in the current database/uuid via a transaction.

```php
number = api->txCount(['gas_price' => 10]);
```

| Argument | Description |
| :--- | :--- |
| gas_info | Object containing gas parameters (see above) |

Returns a promise resolving to an integer value.

### deleteAll\($gas_info\)

Remove all keys in the current database/uuid.

```php
api->deleteAll([gas_price => 10]);
```

| Argument | Description |
| :--- | :--- |
| gas_info | Object containing gas parameters (see above) |

Returns a promise resolving to nothing.

### keyValues\(\)

Enumerate all keys and values in the current database/uuid. This function bypasses the consensus and cryptography mechanisms in favor of speed.

```php
kvs = api->keyValues();
```

Returns a promise resolving to a JSON array containing key/value pairs, e.g.

```
[{"key": "key1", "value": "value1"}, {"key": "key2", "value": "value2"}]
```

### txKeyValues\($gas_info\)

Enumerate all keys and values in the current database/uuid via a transaction.

```php
kvs = api->txKeyValues(['gas_price' => 10]);
```

| Argument | Description |
| :--- | :--- |
| gas_info | Object containing gas parameters (see above) |

Returns a promise resolving to a JSON array containing key/value pairs, e.g.

```
[{"key": "key1", "value": "value1"}, {"key": "key2", "value": "value2"}]
```

### multiUpdate\($key_values, $gas_info\)

Update multiple fields in the database.

```php
api->multiUpdate([['key' => "key1", 'value' => "value1"], ['key' => "key2", 'value' => "value2"], ['gas_price': 10]]);
```

| Argument | Description |
| :--- | :--- |
| key_values | An array of objects containing keys and values (see example avove) |
| gas_info | Object containing gas parameters (see above) |

Returns a promise resolving to nothing.

Throws an exception when any of the keys doesn't exist.


### getLease\($key\)

Retrieve the minimum time remaining on the lease for a key. This function bypasses the consensus and cryptography mechanisms in favor of speed.

```php
value = api->getLease('mykey');
```

| Argument | Description |
| :--- | :--- |
| key | The key to retrieve the lease information for |

Returns a promise resolving the minimum length of time remaining for the key's lease, in seconds.

Throws an exception when the key does not exist in the database.

### txGetLease\($key, $gas_info\)

Retrieve the minimum time remaining on the lease for a key, using a transaction.

```php
value = api->txGetLease('mykey', ['gas_price' => 10]);
```

| Argument | Description |
| :--- | :--- |
| key | The key to retrieve the lease information for |
| gas_info | Object containing gas parameters (see above) |

Returns a promise resolving the minimum length of time remaining for the key's lease, in seconds.

Throws an exception when the key does not exist in the database.

### renew_lease\($key, $gas_info, $lease_info\)

Update the minimum time remaining on the lease for a key.

```php
value = api->renewLease('mykey', ['max_fee' => '400001'], [ 'days' => 100 ]);
```

| Argument | Description |
| :--- | :--- |
| key | The key to retrieve the lease information for |
| gas_info | Object containing gas parameters (see above) |
| lease_info (optional) | Minimum time for key to remain in database (see above) |

Returns a promise resolving the minimum length of time remaining for the key's lease.

Throws an exception when the key does not exist in the database.


### renewLeaseAll\($gas_info, $lease_info\)

Update the minimum time remaining on the lease for all keys.

```php
value = api->renewLeaseAll(['max_fee' => '400001'], [ 'days' => 100 ]);
```

| Argument | Description |
| :--- | :--- |
| gas_info | Object containing gas parameters (see above) |
| lease_info (optional) | Minimum time for key to remain in database (see above) |

Returns a promise resolving the minimum length of time remaining for the key's lease.

Throws an exception when the key does not exist in the database.


### getNShortestLease\($n\)

Retrieve a list of the n keys in the database with the shortest leases.  This function bypasses the consensus and cryptography mechanisms in favor of speed.
 
```php

keys = api->getNShortestLease(10);

```

| Argument | Description |
| :--- | :--- |
| n  | The number of keys to retrieve the lease information for |

Returns a JSON array of objects containing key, lease (in seconds), e.g.
```
[ { key: "mykey", lease: { seconds: "12345" } }, {...}, ...]
```

### txGetNShortestLease\($n, $gas_info\)

Retrieve a list of the N keys/values in the database with the shortest leases, using a transaction.
 
```php

keys = api.txGetNShortestLease(10, ['max_fee' => '400001']);

```

| Argument | Description |
| :--- | :--- |
| n | The number of keys to retrieve the lease information for |
| gas_info | Object containing gas parameters (see above) |

Returns a JSON array of objects containing key, lifetime (in seconds), e.g.
```
[ { key: "mykey", lifetime: "12345" }, {...}, ...]
```

## Development

After checking out the repo, run `bin/setup` to install dependencies. Then, run `rake spec` to run the tests. You can also run `bin/console` for an interactive prompt that will allow you to experiment.

To install this gem onto your local machine, run `bundle exec rake install`. To release a new version, update the version number in `version.rb`, and then run `bundle exec rake release`, which will create a git tag for the version, push git commits and tags, and push the `.gem` file to [phpgems.org](https://phpgems.org).

## Contributing

Bug reports and pull requests are welcome on GitHub at https://github.com/mul53/bluzelle. This project is intended to be a safe, welcoming space for collaboration, and contributors are expected to adhere to the [code of conduct](https://github.com/mul53/bluzelle/blob/master/CODE_OF_CONDUCT.md).


## License

The gem is available as open source under the terms of the [MIT License](https://opensource.org/licenses/MIT).

## Code of Conduct

Everyone interacting in the Bluzelle project's codebases, issue trackers, chat rooms and mailing lists is expected to follow the [code of conduct](https://github.com/mul53/bluzelle/blob/master/CODE_OF_CONDUCT.md).


[ico-version]: https://img.shields.io/packagist/v/bluzelle/blzphp.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/bluzelle/blzphp/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/bluzelle/blzphp.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/bluzelle/blzphp.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/bluzelle/blzphp.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/bluzelle/blzphp
[link-travis]: https://travis-ci.org/bluzelle/blzphp
[link-scrutinizer]: https://scrutinizer-ci.com/g/bluzelle/blzphp/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/bluzelle/blzphp
[link-downloads]: https://packagist.org/packages/bluzelle/blzphp
[link-author]: https://github.com/mul53
[link-contributors]: ../../contributors
