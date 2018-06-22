<?php

namespace Iapps\HoldingAccountService\ValueObject;

use Iapps\Common\Encryptor;
use Iapps\Common\Helper\FieldEncryptionInterface;

class Passcode {

    private $code;
    private $encoded_code = NULL;

    private $encryptor;

    function __construct(FieldEncryptionInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    public function setCode($code)
    {
        $this->code = $code;
        $this->encoded_code = $this->encodeValue($this->code);

        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setEncodedCode($encoded_code)
    {
        $this->encoded_code = $encoded_code;
        $this->code = $this->decodeValue($this->encoded_code);

        return $this;
    }

    public function getEncodedCode()
    {
        return $this->encoded_code;
    }

    protected function encodeValue($value)
    {
        return $this->encryptor->encrypt($value);
    }

    protected function decodeValue($value)
    {
        return $this->encryptor->decrypt($value);
    }

    public function authorize($check_pass)
    {
        return ($this->getCode() == $check_pass);
    }
}