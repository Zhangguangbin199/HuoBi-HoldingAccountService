<?php
    $paymentMode = 0;
    $paymentModeFee = 0;
    $transactionFee = 0;
    $totalPayable = 0;
    $itemDescription = array();
    $paymentModeSubTotal = 0;
    $discountDescription = array();
    $totalDiscount = 0;
    $totalCharges = 0;
    $paymentModeDescription1 = array();
    $paymentModeDescription2 = array();
    $transactionId = NULL;
    $transactionDate = NULL;
    $subTotal = 0;
    $paymentModeName = NULL;
    $transactionID = $transactionDetail->transaction->getTransactionID();

    $transactionDetail->transaction->getCreatedAt()->setTimeZoneFormat($timezone_format);
    $transactionDateStr = $transactionDetail->transaction->getCreatedAt()->getLocalDateTimeStr();

    $paymentMode = ($transactionDetail->payment)? $transactionDetail->payment[0] : array();;
    $transactionType = $transactionDetail->transaction->getTransactionType()->getDisplayName();
    $countryCurrencyCode = $transactionDetail->transaction->getCountryCurrencyCode();
    $agentName = NULL;

    foreach ($transactionDetail->transaction_items AS $k =>  $v){


        switch ($v->getItemType()->getCode()) {

            case 'corporate_service':
                $itemDescription = json_decode($v->getDescription());
                $paymentModeSubTotal = $v->getNetAmount();
                break;
            case 'corporate_service_fee':

                $transactionFee = $v->getNetAmount();
                break;
            case 'payment_fee':
                $paymentModeFee = $v->getNetAmount();
                break;
            case 'discount':
                $discountDescription =  json_decode($v->getDescription());
                $totalDiscount = $v->getNetAmount();
                break;
        }
    }


    $totalCharges = $paymentModeFee + $transactionFee;
    $totalPayable = $transactionDetail->transaction_items->getTotalAmount();
    $paymentModeName = isset($paymentMode->payment_mode_name) ? $paymentMode->payment_mode_name : "";

    $temp = array_merge( json_decode($paymentMode->description1) , json_decode($paymentMode->description2));

    $agentName = "";
    if($paymentMode->agent_id != NULL) {
        foreach ($temp AS $k => $v) {
            $agentName .= $v->value . ',';
        }

        $agentName = rtrim($agentName, ",");
    }

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/html">
<body>

<div style="width: 960px;margin: 0 auto; padding: 0;font-family:Arial,'Times New Roman','Microsoft YaHei',SimHei;">
    <div style="width: 100%;border:0px;background: #f5f5f5;height:96px;display: block;overflow: hidden;">
        <div style="margin: 0;padding: 0;padding-top: 15px;padding-right: 15px; width: 100px;text-align: right; float: left">
            <img src="https://s3-ap-southeast-1.amazonaws.com/slideproduction/public/images/logo.gif" style="">
        </div>

        <div style="width:300px; float: left">
            <p style="margin: 0;padding: 0;margin-top: 10px;"><span style="color:#e92b83;">Thank you for choosing SLIDE</span></p>
            <p style="margin: 0;padding: 0;margin-top: 10px;"><span style="color:#4A4A4A;font-size: 40px; font-weight: bold "><?php echo $currencyFormatter::format($totalPayable , $countryCurrencyCode); ?></span></p>

        </div>
        <div style="width:300px; float: right;  text-align: right; padding-right: 20px">
            <p style="margin: 0;padding: 0;margin-top: 10px;"><span style="color:#4A4A4A; font-size: 20px"><b><?php echo $transactionType?></b></span></p>
            <p style="margin: 0;padding: 0;margin-top: 10px;"><span style="color:#8f8f8f; font-size: 14px">Transaction ID <?php echo $transactionID?></span></p>
            <p style="margin: 0;padding: 0;margin-top: 2px;"><span style="color:#8f8f8f; font-size: 14px"><?php echo $transactionDateStr?></span></p>
        </div>
    </div>

    <div style="clear:both;"></div>
    <div style="width: 94%;overflow: hidden;margin: auto;" >


        <div style="float:left; width: 100%;" >
            <h3 style="color:#e92b83;font-size: 20px;">ITEM DESCRIPTION</h3>

            <div style="padding: 0;margin: 0;font-size: 14px;font-weight: normal;">
                <?php foreach ($itemDescription AS $k => $v):?>
                    <p style="font-size: 14px"><span style="float: right;color:#000;"><?php echo $v->value ?></span><span style="color:#000"><?php echo $v->title ?></span></p>
                    <div style="clear:both;"></div>
                <?php endforeach;?>

            </div>
        </div>
        <div style="clear:both;"></div>
        <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

        <p style="font-size: 14px;"><span style="float: right;color: #000;"><b> <?php echo $currencyFormatter::format($paymentModeSubTotal, $countryCurrencyCode); ?>  </b></span> <span style="color:#4A4A4A;"><b>Sub Total</b></span></p>


        <div style="float:left; width: 100%;" >
            <u style="color:#666;">Charges</u>
            <div style="padding: 0;margin: 0;font-size: 14px;font-weight: normal;">
                <?php if($paymentMode->payment_code != 'NIL') { ?>
                    <p><span style="float: right;color: #000;font-size: 14px;"> <?php echo $paymentModeName ?>  </span> <span style="color:#4A4A4A"> Payment Mode </span></p>
                <?php } ?>
                <p><span style="float: right"> <b><?php echo $currencyFormatter::format($totalCharges, $countryCurrencyCode); ?></b>  </span> <span style="color:#4A4A4A"><b>Total Charges</b> </span></p>

            </div>
        </div>

        <?php if($totalDiscount){?>
            <div style="float:left; width: 100%;margin-top: 24px;" >
                <u style="color:#4A4A4A;">Discount</u>
                <div style="padding: 0;margin: 0;font-size: 14px;font-weight: normal;">
                    <p><span style="float: right;color:#000""><b><?php echo $currencyFormatter::format($totalDiscount, $countryCurrencyCode); ?> </b> </span><span style="color:#4A4A4A"><b>Total Discount</b></span></p>
                </div>

            </div>

        <?php }?>

        <div style="clear:both;"></div>
        <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

        <p style="text-align: right;color:#4A4A4A;"><b>Total Payable : </b><span style="color: #e92b83"><b><?php echo $currencyFormatter::format($totalPayable , $countryCurrencyCode); ?> </span></b></p>

        <div style="clear:both;"></div>
        <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

        <div style="padding-top: 15px;">
            <?php if ($agentProfileImageUrl){?>
                <div style="float: left;">
                    <img src="<?php echo $agentProfileImageUrl; ?>" alt="" style="width:100px; height:100px; border-radius:50%; overflow:hidden;">
                </div>
            <?php } ?>
            <div style="float: left;padding-top: 50px;padding-bottom: 50px; padding-left: 30px;color:#4A4A4A">
                <?php echo $agentName;?>
            </div>

        </div>


        <div style="clear:both;"></div>
        <div style="padding: 0;margin:0 auto;margin-top: 24px;margin-bottom: 24px; width: 100%; height: 1px ;background:#8f8f8f;;"></div>

        <p style="text-align: center;">
            <span style="color: #e92b83"><b>NEED HELP?</b></span> Contact us at helpme.id@slide.club
        </p>

        <div  style="width: 100%;border:0px;background: #e6086f;height:96px;display: block;overflow: hidden; color: #FFF;text-align: center;">
            <p>Thank you for using SLIDE and have a nice day.</p>
            <p>Terms and conditions apply.</p>
        </div>
    </div>

    </div>

</body>
</html>
