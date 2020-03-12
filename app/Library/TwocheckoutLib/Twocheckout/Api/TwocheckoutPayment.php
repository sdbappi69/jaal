<?php
namespace App\Library\TwocheckoutLib\Twocheckout\Api;

use App\Library\TwocheckoutLib\Twocheckout;
use App\Library\TwocheckoutLib\Twocheckout\Api\Twocheckout_Api_Requester;
use App\Library\TwocheckoutLib\Twocheckout\Api\Twocheckout_Util;

class Twocheckout_Payment extends Twocheckout
{

    public static function retrieve()
    {
        $request = new Twocheckout_Api_Requester();
        $urlSuffix = '/api/acct/list_payments';
        $result = $request->doCall($urlSuffix);
        $response = Twocheckout_Util::returnResponse($result);
        return $response;
    }

    public static function pending()
    {
        $request = new Twocheckout_Api_Requester();
        $urlSuffix = '/api/acct/detail_pending_payment';
        $result = $request->doCall($urlSuffix);
        $response = Twocheckout_Util::returnResponse($result);
        return $response;
    }

}