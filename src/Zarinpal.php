<?php

namespace patriotblog\yii_zarinpal;

use SoapClient;


class Zarinpal extends \CModel
{
    public $merchant_id;
    public $callback_url;
    public $testing = false;
    private $status;
    private $authority;
    private $redirectUrl;
    private $_ref_id;

    public function attributeNames(){
        return array(
            'status',
            'authority',
            'redirectUrl'
            );
    }

    /**
     * @param int $amount
     * @param string $description
     * @param null|string $email
     * @param null|string $mobile
     * @param array $callbackParams
     * @return $this
     */
    public function request($amount, $description, $email = null, $mobile = null, $callbackParams = [])
    {
        if(count($callbackParams) > 0){
            $i = 0;
            foreach ($callbackParams as $name => $value){
                if($i == 0) {
                    $this->callback_url .= '?';
                }else{
                    $this->callback_url .= '&';
                }
                $this->callback_url .= $name.'='.$value;
                $i++;
            }
        }
        
        if($this->testing){
            $client = new SoapClient('https://sandbox.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);
        }else{
            $client = new SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);
        }
        $result = $client->PaymentRequest(
            [
                'MerchantID'  => $this->merchant_id,
                'Amount'      => $amount,
                'Description' => $description,
                'Email'       => $email,
                'Mobile'      => $mobile,
                'CallbackURL' => $this->callback_url,
            ]
        );

        $this->status = $result->Status;
        $this->authority = $result->Authority;

        return $this;
    }

    public function verify($authority, $amount)
    {
        $this->authority = $authority;
        if($this->testing){
            $client = new SoapClient('https://sandbox.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);
        }else{
            $client = new SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);
        }
        $result = $client->PaymentVerification(
            [
                'MerchantID' => $this->merchant_id,
                'Authority'  => $this->authority,
                'Amount'     => $amount,
            ]
        );

        $this->status = $result->Status;
        $this->_ref_id = $result->RefID;

        return $this;
    }

    public function getRedirectUrl($zaringate = true)
    {
        if($this->testing){
            $url = 'https://sandbox.zarinpal.com/pg/StartPay/'. $this->authority;
        }else{
            $url = 'https://www.zarinpal.com/pg/StartPay/'.$this->authority;
        }
        $url .=  ($zaringate) ? '/ZarinGate' : '';

        return $url;
    }

    public function getAuthority()
    {
        return $this->authority;
    }
}
