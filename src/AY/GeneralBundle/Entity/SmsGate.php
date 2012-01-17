<?php

namespace AY\GeneralBundle\Entity;

class SmsGate {

    private $login;
    private $pass;

    public function getLogin() {
        return $this->login;
    }

    public function setLogin($login) {
        $this->login = $login;
    }

    public function getPass() {
        return $this->pass;
    }

    public function setPass($pass) {
        $this->pass = $pass;
    }

    public function sendSms($phone, $text) {
        if (!$this->pass || !$this->login || !$phone || !$text)
            return;

        $request = array(
            "method"   => "push_msg",
            "email"    => $this->getLogin(),
            "password" => $this->getPass(),
            "phone"    => $phone,
            "text"     => $text,
            "format"   => "json",
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://api.sms24x7.ru/");
        curl_setopt($curl, CURLOPT_POST, True);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, True);

        $data = curl_exec($curl);
        curl_close($curl);

        if (!$data) 
            return;

        $js = json_decode($data, $assoc = true);

        if (!isset($js['response'])) 
            return;

        $rs = &$js['response'];

        if (!isset($rs['msg'])) 
            return;

        $msg = &$rs['msg'];

        if (!isset($msg['err_code'])) 
            return;

        $ec = intval($msg['err_code']);

        if (!isset($rs['data'])) { 
            $data = NULL; 
        } else { 
            $data = $rs['data']; 
        }

        return array($ec, $data);
    }
}

