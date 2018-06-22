<?php

namespace Iapps\HoldingAccountService\ValueObject;

use Iapps\Common\Encryptor;
use Iapps\Common\Helper\FieldEncryptionInterface;

class HoldingAccountValue {

    private $value          = 0.0;
    private $encoded_value  = NULL;

    private $ori_value          = 0.0;
    private $ori_encoded_value  = NULL;

    private $encryptor;

    function __construct(FieldEncryptionInterface $encryptor)
    {
        $this->encryptor = $encryptor;
        $this->setValue(0.0);
    }

    /*
     * Original only be set through encoded value
     */
    public function setOriginalEncodedValue($encoded_value)
    {
        $this->ori_encoded_value = $encoded_value;
        $this->ori_value = $this->decodeValue($this->ori_encoded_value);
        $this->setValue($this->ori_value);
        return $this;
    }

    public function getOriginalEncodedValue()
    {
        return $this->ori_encoded_value;
    }

    public function setOriginalValue($ori_value)
    {
        $this->ori_value = $ori_value;
        $this->setValue($this->ori_value);
        return $this;
    }

    public function getOriginalValue()
    {
        return $this->ori_value;
    }

    public function setValue($value)
    {
        if( !is_numeric($value) )
            return false;

        $this->value = round($value, 2);
        $this->encoded_value = $this->encodeValue($this->value);
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setEncodedValue($encoded_value)
    {
        $this->encoded_value = $encoded_value;
        $this->value = $this->decodeValue($this->encoded_value);
        return true;
    }

    public function getEncodedValue()
    {
        return $this->encoded_value;
    }

    protected function encodeValue($value)
    {
        return $this->encryptor->encrypt($value);
    }

    protected function decodeValue($value)
    {
        return $this->encryptor->decrypt($value);
    }

    public function getMovementValue()
    {
        return $this->getValue() - $this->getOriginalValue();
    }
}