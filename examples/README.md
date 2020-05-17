# Example
This is an example of how to use the [bluzelle/blzphp](https://github.com/mul53/blzphp) library

## Installation

Install the required dependencies
- Install [Git](https://gist.github.com/derhuerst/1b15ff4652a867391f03)
- Install [PHP](https://www.php.net/manual/en/install.php)
- Install [composer](https://getcomposer.org/download/)

Install dependencies required by packages

Add your php version in [php-verison]
- Install gmp run `sudo apt-get install php[php-verison]-gmp`

After installing the dependencies
- **Only for package testing** Set option `minimum-stability` in your `composer.json` to `dev` 
- Run `composer require mul53/blzphp` in directory root

Example index.php
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Bluzelle\Client;


$client = new Client(
    'bluzelle1upsfjftremwgxz3gfy0wf3xgvwpymqx754ssu9',
    'around buzz diagram captain obtain detail salon mango muffin brother morning jeans display attend knife carry green dwarf vendor hungry fan route pumpkin car',
    'http://testnet.public.bluzelle.com:1317',
    'bluzelle',
    '20fc19d4-7c9d-4b5c-9578-8cedd756e0ea'
);

$gasInfo = ['max_fee' => 4000000];

echo $client->version();
```

- Add the script above in a `index.php` file
- Run `php index.php`

To run more tests copy the script in `examples/index.php` and run `php index.php`
