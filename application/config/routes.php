<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

$route['system/test'] = 'holdingaccount/System_holding_account/test';
$route['system/holdingaccount/create'] = 'holdingaccount/System_holding_account/createHoldingAccount';
$route['user/holdingaccount/create'] = 'holdingaccount/User_holding_account/createHoldingAccount';


//Corp Serv
$route['corp/serv/list'] = 'common/Corporate_service/getAllCorpService';
$route['corp/serv/add'] = 'common/Corporate_service/addCorpService';
$route['corp/serv/edit'] = 'common/Corporate_service/editCorpService';
$route['corp/serv/get'] = 'common/Corporate_service/getCorpServiceInfo';
$route['corp/serv/remove'] = 'common/Corporate_service/removeCorpService';

//Corp Service Fee
$route['corp/serv/fee/add'] = 'common/Corporate_service_fee/addCorpServiceFee';
$route['corp/serv/fee/edit'] = 'common/Corporate_service_fee/editCorpServiceFee';
$route['corp/serv/fee/get'] = 'common/Corporate_service_fee/getCorporateServiceFeeInfo';
$route['corp/serv/fee/get/corpservid'] = 'common/Corporate_service_fee/getCorporateServiceFeeByCorpServId';

//Corporate Service Payment Mode
$route['corp/serv/paymentmode/get/corpservid'] = 'common/Corporate_service_payment_mode/getCorporateServicePaymentModeByCorporateServiceId';
$route['corp/serv/paymentmode/add'] = 'common/Corporate_service_payment_mode/addCorporateServicePaymentMode';
$route['corp/serv/paymentmode/edit'] = 'common/Corporate_service_payment_mode/editCorporateServicePaymentMode';
$route['corp/serv/paymentmode/remove'] = 'common/Corporate_service_payment_mode/removeCorporateServicePaymentMode';
$route['corp/serv/paymentmode/fee/get/corpservid'] = 'common/Corporate_service_payment_mode/getCorporateServicePaymentModeWithFeeByCorporateServiceId';
$route['corp/serv/paymentmode/get'] = 'common/Corporate_service_payment_mode/getCorporateServicePaymentModeInfo';

//Corporate Service Payment Mode Fee
$route['corp/serv/paymentmode/fee/get/paymentmodeid'] = 'common/Corporate_service_payment_mode_fee/getCorporateServicePaymentModeFeeByCorporateServicePaymentModeId';
$route['corp/serv/paymentmode/fee/add'] = 'common/Corporate_service_payment_mode_fee/addCorporateServicePaymentModeFee';
$route['corp/serv/paymentmode/fee/edit'] = 'common/Corporate_service_payment_mode_fee/editCorporateServicePaymentModeFee';
$route['corp/serv/paymentmode/fee/remove'] = 'common/Corporate_service_payment_mode_fee/removeCorporateServicePaymentModeFee';
$route['corp/serv/paymentmode/fee/get'] = 'common/Corporate_service_payment_mode_fee/getCorporateServicePaymentModeFeeInfo';

//USER
$route['user/holdingaccounts/get/all'] = 'holdingaccount/User_holding_account/getHoldingAccounts';
$route['user/holdingaccounts/get'] = 'holdingaccount/User_holding_account/searchHoldingAccounts';
//Search by reference id

$route['user/topup/channel'] = 'common/User_corporate_service/getTopUpChannel';
$route['user/withdrawal/channel'] = 'common/User_corporate_service/getWithdrawalChannel';

$route['user/self/holdingaccount/topup/request'] = 'holdingaccountrequest/User_self_holding_account_request/requestTopup';
$route['user/self/holdingaccount/topup/complete'] = 'holdingaccountrequest/User_self_holding_account_request/completeTopup';
$route['user/self/holdingaccount/topup/cancel'] = 'holdingaccountrequest/User_self_holding_account_request/cancelTopup';

$route['user/holdingaccount/withdrawal/request/list'] = 'holdingaccountrequest/User_self_holding_account_request/retrieveActiveWithdrawalRequest';
$route['user/holdingaccount/withdrawal/request'] = 'holdingaccountrequest/User_self_holding_account_request/requestWithdrawal';
$route['user/holdingaccount/withdrawal/complete'] = 'holdingaccountrequest/User_self_holding_account_request/completeWithdrawal';
$route['user/holdingaccount/withdrawal/cancel'] = 'holdingaccountrequest/User_self_holding_account_request/cancelWithdrawal';
$route['user/holdingaccount/withdrawal/request/list/all'] = 'holdingaccountrequest/User_self_holding_account_request/retrieveAllWithdrawalRequest';

$route['user/holdingaccount/utilize/request'] = 'holdingaccountrequest/User_holding_account_request/requestUtilise';
$route['user/holdingaccount/utilize/complete'] = 'holdingaccountrequest/User_holding_account_request/completeUtilise';
$route['user/holdingaccount/utilize/cancel'] = 'holdingaccountrequest/User_holding_account_request/cancelUtilise';


$route['holdingaccount/collection/request'] = 'holdingaccountrequest/Holding_account_request/requestHoldingAccountCollection';
$route['holdingaccount/collection/complete'] = 'holdingaccountrequest/Holding_account_request/completeHoldingAccountCollection';
$route['holdingaccount/collection/cancel'] = 'holdingaccountrequest/Holding_account_request/cancelHoldingAccountCollection';

/*
 * Holding Account for system
 */
$route['system/user/currency/get'] = 'holdingaccount/System_holding_account/getUserHoldingAccounts';
$route['system/topup/channel'] = 'common/System_corporate_service/getTopUpChannel';
$route['system/holdingaccount/utilize/cancel'] = 'holdingaccountrequest/System_holding_account_request/cancelUtilise';
$route['system/holdingaccount/utilize/request'] = 'holdingaccountrequest/System_holding_account_request/requestUtilise';
$route['system/holdingaccount/utilize/complete'] = 'holdingaccountrequest/System_holding_account_request/completeUtilise';

/*
 *  agent Holding Account Movement History
 */
$route['agent/history/list'] = 'holdingaccount/Agent_holding_account/getHoldingAccountHistory';
$route['agent/history/listbydate'] = 'holdingaccount/Agent_holding_account/getHoldingAccountHistoryByDate';

$route['agent/holdingaccounts/get'] = 'holdingaccount/Agent_holding_account/searchHoldingAccounts';
/*
 *  user Holding Account Movement History
 */
$route['user/history/list'] = 'holdingaccount/User_holding_account/getHoldingAccountHistory';
$route['user/history/listbydate'] = 'holdingaccount/User_holding_account/getHoldingAccountHistoryByDate';


/*
  admin transaction history
*/
$route['admin/transaction/listbyrefidarr'] = 'holdingaccounttransaction/Admin_transaction/getTransactionListForUserByRefIDArr';
$route['admin/transaction/history/detail'] = 'holdingaccounttransaction/Admin_transaction/getTransactionHistoryDetailByTransactionId';

$route['admin/transaction/history/listbyidarr'] = 'holdingaccounttransaction/Admin_transaction/getTransactionListByRefIDArr';

/*
  partner transaction history
*/
$route['partner/transaction/listbyrefidarr'] = 'holdingaccounttransaction/Partner_transaction/getTransactionListForUserByRefIDArr';
$route['partner/transaction/history/detail'] = 'holdingaccounttransaction/Partner_transaction/getTransactionHistoryDetailByTransactionId';


/*
  user transaction history
*/
$route['user/transaction/history/list']         = 'holdingaccounttransaction/User_transaction/getTransactionHistoryList';
$route['user/transaction/history/detail']       = 'holdingaccounttransaction/User_transaction/getTransactionHistoryDetailByTransactionId';
$route['user/transaction/history/detail/refid'] = 'holdingaccounttransaction/User_transaction/getTransactionHistoryDetailByRefId';
$route['user/transaction/history/listbydate']   = 'holdingaccounttransaction/User_transaction/getTransactionHistoryListByDate';

$route['user/transaction/history/listbyidarr']  = 'holdingaccounttransaction/User_transaction/getTransactionListByRefIDArr';


/*
 * AUDIT LOG
 */
$route['log/listen'] = 'common/Audit_log/listenLogEvent';

$route['holdingaccount/log'] = 'holdingaccount/User_holding_account/getAuditLog';

/*
 * Batch Job
 */
$route['job/holdingaccount/request/autocancel'] = 'holdingaccountrequest/System_holding_account_request/autoCancelRequest';
$route['job/holdingaccount/receipt/generate'] = 'common/Batch_job/listenGenerateReceipt';
