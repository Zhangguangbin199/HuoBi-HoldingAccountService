<?php

namespace Iapps\HoldingAccountService\ValueObject;

use Iapps\HoldingAccountService\Common\Rijndael256EncryptorFactory;

class HoldingAccountValueFactory {

    public static function build()
    {
        $encryptor = Rijndael256EncryptorFactory::build();
        return new HoldingAccountValue($encryptor);
    }
}