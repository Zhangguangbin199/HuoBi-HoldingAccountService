<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFee;
use Iapps\Common\CorporateService\FeeType;
use Iapps\Common\Microservice\PromoCode\PromoCodeClientFactory;
use Iapps\Common\Microservice\PromoCode\PromoTransactionType;
use Iapps\HoldingAccountService\Common\CorporateServServiceFactory;
use Iapps\HoldingAccountService\Common\Logger;
use Iapps\HoldingAccountService\Common\PaymentDirection;
use Iapps\Common\Microservice\PromoCode\PromoCodeClient;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequest;

class HoldingAccountFeeCalculator extends IappsBasicBaseService{

    protected $in_amount = 0;
    protected $in_country_currency_code;
    protected $transaction_items;

    protected $corporate_service;
    protected $corporate_service_fee;
    protected $cash_in_mode;
    protected $_holdingAccountRequest;

    function __construct()
    {
        parent::__construct();

        $this->transaction_items = new HoldingAccountTransactionItemCollection();
        $this->_holdingAccountRequest = new HoldingAccountRequest();
    }

    public function getInAmount()
    {
        return $this->in_amount;
    }

    public function getInCountryCurrencyCode()
    {
        return $this->in_country_currency_code;
    }

    public function getTransactionItems()
    {
        return $this->transaction_items;
    }

    public function getPaymentAmount()
    {
        return $this->transaction_items->getTotalAmount();
    }

    public function getCorporateService()
    {
        return $this->corporate_service;
    }

    public function getCashInMode()
    {
        return $this->cash_in_mode;
    }

    public static function calculate($corporate_service_id,
                                     HoldingAccountRequest $request,
                                     $self_service = FALSE,
                                     $promo_id = NULLL)
    {
        Logger::debug('Start calculation: ' . $request->getId());
        $amount = $request->getAmount();
        $payment_mode = $request->getPaymentCode();

        $c = new HoldingAccountFeeCalculator();
        $c->_holdingAccountRequest = $request;

        //get corporate service
        $corp_serv = CorporateServServiceFactory::build();

        if( $amount >= 0 )
            $direction = PaymentDirection::IN;
        else
            $direction = PaymentDirection::OUT;

        if( $c->corporate_service = $corp_serv->getCorporateService($corporate_service_id) )
        {
            if( $payment_mode )
                $c->corporate_service_fee = $corp_serv->getCorpServiceFeeByCorpServId($corporate_service_id, $direction, $payment_mode, $self_service);

            Logger::debug('Extracted corporate_service & fee: ' . $request->getId());

            $c->in_country_currency_code = $c->corporate_service->getCountryCurrencyCode();
            $c->in_amount = $amount;
            $c->cash_in_mode = $payment_mode != 'NIL' ? $payment_mode : NULL;

            if( $c->_generateCorporateServiceItem() AND
                $c->_computeFees() )
            {
                Logger::debug('Computed main item & fee: ' . $request->getId());
                if( $promo_id )
                {
                    if( !$c->_computePromo($promo_id) )
                    {
                        Logger::debug('Failed to compute discount: ' . $request->getId());
                        return false;
                    }
                }

                return $c;
            }
        }

        Logger::debug('Failed unknown reason: ' . $request->getId());
        return false;
    }

    protected function _computeFees()
    {
        if( $this->cash_in_mode != NULL )
        {
            //cash in payment fee
            if( isset($this->corporate_service_fee->payment_mode) )
            {
                return $this->_computePaymentFee($this->corporate_service_fee->payment_mode, $this->cash_in_mode);
            }

            Logger::debug('Failed to compute fee: ' . $this->cash_in_mode);
            return false;
        }

        return true;
    }

    protected function _computePaymentFee(array $payment_modes, $used_mode)
    {
        foreach( $payment_modes as $mode)
        {
            if($mode['payment_code'] == $used_mode )
            {
                if( isset($mode['fee']) )
                {
                    $service_fee = 0.0;

                    //compute service fees first
                    foreach($mode['fee'] AS $feeitem)
                    {
                        if( $feeitem instanceof CorporateServicePaymentModeFee)
                        {
                            if($feeitem->getFeeType()->getCode() == FeeType::SERVICE_FEE)
                            {
                                $service_fee += $this->_generateFeeItem($feeitem, ItemType::CORPORATE_SERVICE_FEE, $this->getInAmount());
                            }
                        }
                    }

                    //compute payment fees on top of service fee
                    foreach($mode['fee'] AS $feeitem)
                    {
                        if( $feeitem instanceof CorporateServicePaymentModeFee)
                        {
                            if($feeitem->getFeeType()->getCode() == FeeType::PAYMENT_MODE_FEE)
                            {
                                $this->_generateFeeItem($feeitem, ItemType::PAYMENT_FEE, $this->getInAmount() + $service_fee);
                            }
                        }
                    }
                }

                return $this;
            }
        }

        Logger::debug('Failed getting payment mode: ' . $used_mode);
        return false;
    }

    protected function _generateCorporateServiceItem()
    {
        $item = new HoldingAccountTransactionItem();

        $item->getItemType()->setCode(ItemType::CORPORATE_SERVICE);
        $item->setItemId($this->getCorporateService()->getId());
        $item->setItemInfo($this->getCorporateService());
        $item->setName($this->getCorporateService()->getName());
        $item->setDescription($this->_holdingAccountRequest->getItemDescription()->toJson());
        $item->setUnitPrice($this->getInAmount());
        $item->setCostCountryCurrencyCode($this->getInCountryCurrencyCode());
        $item->setCost($this->getInAmount());

        $this->getTransactionItems()->addData($item);

        return $this;
    }

    protected function _generateFeeItem(CorporateServicePaymentModeFee $feeitem, $item_type_code, $amount)
    {
        $item = new HoldingAccountTransactionItem();
        $item->getItemType()->setCode($item_type_code);
        $item->setItemId($feeitem->getId());
        $item->setItemInfo($feeitem);
        $item->setName($feeitem->getName());
        $item->setDescription($feeitem->getName());

        if( $feeitem->getIsPercentage() == 1 )
        {
            $unit_price = round( abs($amount)*$feeitem->getFee()/100, 2);
            $item->setCost($unit_price);
            $item->setCostCountryCurrencyCode($this->getInCountryCurrencyCode());
        }
        else
        {
            $unit_price = $feeitem->getFee();
            $item->setCost($feeitem->getConvertedFee());
            $item->setCostCountryCurrencyCode($feeitem->getConvertedFeeCountryCurrencyCode());
        }

        $item->setUnitPrice($unit_price);

        $this->getTransactionItems()->addData($item);
        return $item->getNetAmount();
    }

    protected function _computePromo($promo_id)
    {
        $c = PromoCodeClientFactory::build(2);
        if( $reward = $c->check($this->_holdingAccountRequest->getHoldingAccount()->getUserProfileId(), $promo_id) )
        {
            Logger::debug('Extracted promo code reward' . $reward->getId());
            if( $this->getInCountryCurrencyCode() == $reward->getCountryCurrencyCode() AND
                $reward->isType(PromoTransactionType::HOLDING_ACCOUNT) )
            {
                //add promo item
                $item = new HoldingAccountTransactionItem();

                $item->getItemType()->setCode(ItemType::DISCOUNT);
                $item->setItemId($reward->getId());
                $item->setItemInfo($reward);
                $item->setName(strtoupper(str_replace('#', '', $reward->getPromoCode())));
                $item->setDescription(strtoupper(str_replace('#', '', $reward->getPromoCode())));
                if( $this->getTransactionItems()->getTotalAmount() < $reward->getAmount() )
                    $amount = $this->getTransactionItems()->getTotalAmount();
                else
                    $amount = $reward->getAmount();

                $item->setUnitPrice(-1*$amount);
                $item->setCostCountryCurrencyCode($this->getInCountryCurrencyCode());
                $item->setCost(-1*$amount);

                $this->getTransactionItems()->addData($item);
                return true;
            }
        }

        return false;
    }
}