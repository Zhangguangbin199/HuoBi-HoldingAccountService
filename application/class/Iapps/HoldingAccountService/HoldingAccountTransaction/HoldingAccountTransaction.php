<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\Common\Transaction\Transaction;
use Iapps\HoldingAccountService\ValueObject\PasscodeFactory;

class HoldingAccountTransaction extends Transaction{

    function __construct()
    {
        parent::__construct();

        $this->items = new HoldingAccountTransactionItemCollection();
        $this->passcode = PasscodeFactory::build();
    }

    public function getCombinedTransactionArray(array $fields = NULL)
    {
        $transactionArray = $this->getSelectedField($fields);
        $combinedItems = $this->getItems()->groupItemsBySpreadAndFee();

        if( array_key_exists('items', $fields) )
            $transactionArray['items'] = $combinedItems->getSelectedField($fields['items']);
        else
            $transactionArray['items'] = $combinedItems->jsonSerialize();

        return $transactionArray;
    }
}