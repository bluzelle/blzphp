<?php

declare(strict_types=1);

namespace Bluzelle\Bluzelle\Exception;

class InvalidLeaseException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Invalid lease time');
    }
}