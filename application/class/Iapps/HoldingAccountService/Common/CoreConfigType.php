<?php

namespace Iapps\HoldingAccountService\Common;

class CoreConfigType
{
    const REQUEST_EXPIRED_PERIOD = 'request_expired_period';
    const UTILIZE_REQUEST_EXPIRED_PERIOD = 'utilize_request_expired_period';
    const CASHIN_REQUEST_EXPIRED_PERIOD = 'cashin_request_expired_period';
    const COMMISSION_REQUEST_EXPIRED_PERIOD = 'commission_request_expired_period';
    const EXTERNAL_REQUEST_EXPIRED_PERIOD = 'external_request_expired_period';
    const WITHDRAWAL_MESSAGE = 'withdrawal_message';
    const TOPUP_MESSAGE = 'topup_message';
    const TOPUP_EMAIL_SUBJECT = 'topup_email_subject';
    const WITHDRAWAL_EMAIL_SUBJECT = 'withdrawal_email_subject';
    const HOLDING_ACCOUNT_BALANCE_EMAIL_LIST = 'holding_account_balance_report_email_list';
}
