<?php

namespace Iapps\HoldingAccountService\HoldingAccountReport;

use Iapps\Common\CommunicationService\CommunicationServiceProducer;
use Iapps\Common\CommunicationService\EmailAttachment;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\FileUploader\LocalS3FileUploader;
use Iapps\Common\Helper\FileUploader\S3FileUploader;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\HoldingAccountService\Common\CoreConfigType;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountCollection;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountType;
use Iapps\HoldingAccountService\HoldingAccountMovementRecord\HoldingAccountMovementRecordCollection;
use Iapps\HoldingAccountService\HoldingAccountMovementRecord\HoldingAccountMovementRecordServiceFactory;
use Iapps\HoldingAccountService\Otp\CoreConfigDataServiceFactory;

class HoldingAccountBalanceReportService extends HoldingAccountReportBaseService{

    protected function getData()
    {
        if( !$tillDate = $this->getOption('date') )
            return false;

        //get HoldingAccount ids, public HoldingAccount
        $systemCodeServ = SystemCodeServiceFactory::build();
        if( !$publicHoldingAccount = $systemCodeServ->getByCode(HoldingAccountType::PERSONAL_ACCOUNT, HoldingAccountType::getSystemGroupCode()) )
            return false;

        $holdingAccountService = HoldingAccountServiceFactory::build(HoldingAccountType::PERSONAL_ACCOUNT);
        $filter = new HoldingAccount();
        $filter->setHoldingAccountType($publicHoldingAccount);
        if( !$holdingAccountResult = $holdingAccountService->searchByFilter($filter) )
            return false;

        $holdingAccounts = $holdingAccountResult->getResult();
        if( $holdingAccounts instanceof HoldingAccountCollection )
        {
            $data = array();
            $sn = 1;
            foreach( $holdingAccounts AS $holdingAccount )
            {
                $data[$holdingAccount->getId()]['S/N'] = $sn;
                $data[$holdingAccount->getId()]['User Residential Country'] = NULL;
                $data[$holdingAccount->getId()]['User Account ID'] = NULL;
                $data[$holdingAccount->getId()]['User Display Name'] = NULL;
                $data[$holdingAccount->getId()]['User Full Name'] = NULL;
                $data[$holdingAccount->getId()]['Wallet Currency'] = $holdingAccount->getCountryCurrencyCode();
                $data[$holdingAccount->getId()]['Wallet Balance'] = 0.0;

                $sn = $sn+1;
            }

            //combine user information
            $userIds = $holdingAccountResult->getResult()->getUserProfileIds();
            if( count($userIds) > 0 )
            {
                $accountService = AccountServiceFactory::build();
                if( $users = $accountService->getUsers($userIds) )
                {
                    foreach( $users AS $user)
                    {
                        foreach( $holdingAccounts AS $holdingAccount )
                        {
                            if( $holdingAccount->getUserProfileId() == $user->getId() )
                            {
                                $data[$holdingAccount->getId()]['User Residential Country'] = $user->getHostCountryCode();
                                $data[$holdingAccount->getId()]['User Account ID'] = $user->getAccountID();
                                $data[$holdingAccount->getId()]['User Display Name'] = $user->getName();
                                $data[$holdingAccount->getId()]['User Full Name'] = $user->getFullName();
                            }
                        }
                    }
                }
            }

            //get last balance by date
            $movementService = HoldingAccountMovementRecordServiceFactory::build();
            if( $movement = $movementService->getByHoldingAccountIds($holdingAccounts->getIds(), new IappsDateTime(), $tillDate) )
            {
                $movement = $movement->result;
                if( $movement instanceof HoldingAccountMovementRecordCollection )
                {
                    $group = $movement->groupByHoldingAccountId();
                    foreach($group AS $key => $movementCollection)
                    {
                        if( $latest = $movementCollection->getLatestRecord() )
                            $data[$latest->getHoldingAccountId()]['Wallet Balance'] = $latest->getLastBalance()->getValue();
                    }
                }
            }

            return $data;
        }

        return false;
    }

    public function generateCSV($fileName)
    {
        if( $status = parent::generateCSV($fileName) )
        {
            $uploader = new LocalS3FileUploader();
            $uploader->setUploadPath('./upload/report/');
            $uploader->setS3Folder('HoldingAccount/report/');
            $uploader->setFileName($fileName);

            if( $uploader->uploadtoS3(NULL) )
            {
                $attachment = new EmailAttachment();
                $attachment->add($uploader->getFileName(), $uploader->getUrl());
            }

            //set email
            $coreConfig = CoreConfigDataServiceFactory::build();
            if( $emails = $coreConfig->getConfig(CoreConfigType::HOLDING_ACCOUNT_BALANCE_EMAIL_LIST) AND
                isset($attachment) )
            {
                $emails = explode("|", $emails);
                $title = 'HoldingAccount Balance Report';
                $content = '<p>Attachment :' . $fileName . '</p>';

                //send email
                $communication = new CommunicationServiceProducer();
                $communication->sendEmail(getenv('ICS_PROJECT_ID'),$title,$content,$content,$emails, $attachment);
            }

            return $status;
        }

        return false;
    }
}