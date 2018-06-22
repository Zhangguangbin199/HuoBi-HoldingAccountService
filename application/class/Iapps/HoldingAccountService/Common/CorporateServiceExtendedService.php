<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\CorporateService\FeeType;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\Common\CorporateService\CorporateServService;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServiceFeeRepository;
use Iapps\Common\CorporateService\CorporateServiceFeeService;
use Iapps\Common\CorporateService\CorporateServiceFeeCollection;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeCollection;
use Iapps\Common\CorporateService\CorporateServicePaymentModeCollection;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;


class CorporateServiceExtendedService extends CorporateServService {

    public function findByTransactionTypeAndCountryCurrencyCode($transaction_type, $country_currency_code)
    {
        if(!$transType = TransactionTypeValidator::validate($transaction_type))
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_TRANSACTION_TYPE);
            return false;
        }

        if( $corpServObj = $this->getRepository()->findByTransactionTypeAndCountryCurrencyCode($transType->getId(), $country_currency_code))
        {
            return $corpServObj;
        }

        return false;
    }

    public function addCorpService(CorporateService $corporateService, $transaction_type_code)
    {
        if(!$transType = TransactionTypeValidator::validate($transaction_type_code))
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_TRANSACTION_TYPE);
            return false;
        }
        $corporateService->setTransactionTypeId($transType->getId());
        $corporateService->setId(GuidGenerator::generate());

        if( $corporateService = $this->addService($corporateService) )
        {
            $this->setResponseCode($this->getResponseCode());
            return true;
        }

        $this->setResponseCode($this->getResponseCode());
        return false;
    }

    public function editCorpService(CorporateService $corporateService,  $transaction_type_code)
    {
        if(!$transType = TransactionTypeValidator::validate($transaction_type_code))
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_TRANSACTION_TYPE);
            return false;
        }
        $corporateService->setTransactionTypeId($transType->getId());

        if( $corporateService = $this->updateService($corporateService) )
        {
            $this->setResponseCode($this->getResponseCode());
            return true;
        }

        $this->setResponseCode($this->getResponseCode());
        return false;
    }


    public function getCorpServiceFeeByCorpServId($corp_serv_id, $direction = PaymentDirection::IN, $selected_payment_mode = NULL, $self_service = false)
    {
        $resultObject = new \StdClass;
        //$resultObject->fee = null;
        $resultObject->payment_mode = null;
        //$resultObject->total_fee = 0;

        $corp_serv_fee_serv = CorporateServiceFeeExtendedServiceFactory::build();
        $corp_serv_payment_mode_serv = CorporateServicePaymentModeServiceFactory::build();
        $corp_serv_payment_mode_fee_serv = CorporateServicePaymentModeFeeServiceFactory::build();

        if ($corpServObj = $this->getRepository()->findById($corp_serv_id)) {

            $result_pm_array = array();

            //need to get by role_id based on access token
            if ($corpServPaymentModeColl = $corp_serv_payment_mode_serv->getSupportedPaymentMode($corpServObj->getId())) {

                if($selected_payment_mode != NULL)
                {
                    $SupportedMode[] = $selected_payment_mode;
                }
                else
                {
                    if( $self_service )//no filter required
                    {
                        $SupportedMode = array();
                        foreach($corpServPaymentModeColl->result AS $corpServPayamentMode)
                        {
                            $SupportedMode[] = $corpServPayamentMode->getPaymentCode();
                        }
                    }
                    else
                    {
                        $payment_serv = PaymentServiceFactory::build();
                        if( !$SupportedMode = $payment_serv->getSupportedPayment($direction) )
                            $SupportedMode = array();
                    }
                }


                //loop for corpserv payment mode
                foreach ($corpServPaymentModeColl->result as $corpServPaymentModeEach) {

                    if(in_array($corpServPaymentModeEach->getPaymentCode(), $SupportedMode))
                    {
                        $result_pm = $corpServPaymentModeEach->getSelectedField(array('direction', 'corporate_service_id', 'payment_code', 'is_default', 'role_id'));
                        if ($fee_object = $corp_serv_payment_mode_fee_serv->getPaymentModeFeeByCorporateServicePaymentModeId($corpServPaymentModeEach->getId())) {

                            $result_pm['fee'] = $fee_object->result->toArray();
                            $total_service_fee = 0;
                            $total_service_fee_percentage = 0;
                            $total_payment_mode_fee = 0;
                            $total_payment_mode_fee_percentage = 0;

                            //loop for payment mode fee
                            foreach ($fee_object->result as $feeObjectEach) {

                                if($feeObjectEach->getFeeType()->getCode() == FeeType::SERVICE_FEE)
                                {
                                    if ($feeObjectEach->getIsPercentage() == (int)true) {
                                        $total_service_fee_percentage += $feeObjectEach->getFee();
                                    } else {
                                        $total_service_fee += $feeObjectEach->getFee();
                                    }
                                }
                                else if($feeObjectEach->getFeeType()->getCode() == FeeType::PAYMENT_MODE_FEE)
                                {
                                    if ($feeObjectEach->getIsPercentage() == (int)true) {
                                        $total_payment_mode_fee_percentage += $feeObjectEach->getFee();
                                    } else {
                                        $total_payment_mode_fee += $feeObjectEach->getFee();
                                    }
                                }
                            }

                            $result_pm['total_service_fee'] = $total_service_fee;
                            $result_pm['total_service_fee_percentage'] = $total_service_fee_percentage;
                            $result_pm['total_payment_mode_fee'] = $total_payment_mode_fee;
                            $result_pm['total_payment_mode_fee_percentage'] = $total_payment_mode_fee_percentage;
                        }
                        $result_pm_array[] = $result_pm;
                    }

                }
                $resultObject->payment_mode = $result_pm_array;
            }
        }

        $this->setResponseCode($corp_serv_fee_serv::CODE_GET_CORPORATE_SERVICE_FEE_SUCCESS);

        return $resultObject;
    }

    protected function _getAccessToken()
    {
        $headers = getallheaders();

        if( array_key_exists(ResponseHeader::FIELD_X_AUTHORIZATION, $headers) )
            return $headers[ResponseHeader::FIELD_X_AUTHORIZATION];

        return null;
    }
}