<?php

namespace Iapps\HoldingAccountService\HoldingAccountMovementRecord;

use Iapps\Common\Core\IappsBaseEntityCollection;

class HoldingAccountMovementRecordCollection extends IappsBaseEntityCollection{

    public function groupByHoldingAccountId()
    {
        $data = array();

        foreach( $this AS $movementRecord)
        {
            if( $movementRecord instanceof HoldingAccountMovementRecord )
            {
                if( !array_key_exists($movementRecord->getHoldingAccountId(), $data) )
                    $data[$movementRecord->getHoldingAccountId()] = new HoldingAccountMovementRecordCollection();

                $data[$movementRecord->getHoldingAccountId()]->addData($movementRecord);
            }
        }

        return $data;
    }

    public function getLatestRecord()
    {
        foreach( $this AS $movementRecord )
        {
            if( $movementRecord instanceof HoldingAccountMovementRecord )
            {
                $sorted = $this->sortByLatest();
                $sorted->rewind();
                return $sorted->current();
            }
        }

        return false;
    }

    public function sortByLatest()
    {
        $data = $this->toArray();

        if( $sortedArray = usort($data, array($this, "_sortCreatedAt") ))
        {
            $sortedCollection = new HoldingAccountMovementRecordCollection();
            foreach($data AS $movement)
            {
                $sortedCollection->addData($movement);
            }

            return $sortedCollection;
        }

        return $this;
    }

    // Define the custom sort function
    private function _sortCreatedAt($a,$b)
    {
        if( $a instanceof HoldingAccountMovementRecord AND
            $b instanceof HoldingAccountMovementRecord )
        {
            return $a->getCreatedAt()->getUnix() < $b->getCreatedAt()->getUnix();
        }

        //remain same order if
        return false;
    }
}