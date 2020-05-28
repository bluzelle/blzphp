# Example
This is an example of how to use the [bluzelle/blzphp](https://github.com/mul53/blzphp) library

## Installation(ubuntu 18.04)

### Install dependencies
1. `sudo apt-get update`
2. `sudo apt-get install php`
3. `sudo apt-get install composer`
4. `sudo apt-get install php7.2-gmp`

### Setup project
1. Create a folder for your project
2. Init the proejct with composer `composer init`
3. Install package `composer require mul53/blzphp`
4. Create a file called `index.php` and include the code below

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

?>
```
5. Run the file `php index.php`

To run more tests copy the script in `examples/index.php` to your file and run `php index.php`
