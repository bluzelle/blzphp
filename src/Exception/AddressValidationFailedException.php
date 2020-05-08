<?php

declare(strict_types=1);

namespace Bluzelle\Exception;

class AddressValidationFailedException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Bad credentials - verify your address and mnemonic');
    }
}
