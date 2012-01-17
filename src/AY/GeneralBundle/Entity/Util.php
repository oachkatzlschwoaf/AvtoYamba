<?php

namespace AY\GeneralBundle\Entity;

class Util {

    public function cleanPhone($phone) {
        $phone = str_replace(' ', '', $phone);
        $phone = preg_replace('/[^\d]/', '', $phone);

        if (strlen($phone) > 10)
            $phone = preg_replace('/^(7|8)/', '', $phone);

        return $phone;
    }

    public function translateForward($st) {
        $map = array(
            "А" => "a", 
            "В" => "b", 
            "Е" => "e", 
            "К" => "k",
            "М" => "m", 
            "Н" => "h", 
            "О" => "o", 
            "Р" => "p", 
            "С" => "c", 
            "Т" => "t", 
            "У" => "y", 
            "Х" => "x",
            "а" => "a", 
            "в" => "b",
            "е" => "e",
            "к" => "k",
            "м" => "m", 
            "н" => "h",
            "о" => "o", 
            "р" => "p", 
            "с" => "c",
            "т" => "t", 
            "у" => "y",
            "х" => "x"
        );

        $st = strtr($st, $map);
        $st = strtolower($st);

        return $st;
    }

    public function translateBack($st) {
        $map = array(
            "a" => "А", 
            "b" => "В", 
            "e" => "Е", 
            "k" => "К", 
            "m" => "М", 
            "h" => "Н", 
            "o" => "О", 
            "p" => "Р", 
            "c" => "С", 
            "t" => "Т", 
            "y" => "У", 
            "x" => "Х",
        );

        $st = strtr($st, $map);

        return $st;
    }

    public function generatePassword ($length = 8) {
        $password = "";

        $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";

        $maxlength = strlen($possible);

        if ($length > $maxlength) {
            $length = $maxlength;
        }

        $i = 0; 

        while ($i < $length) { 
            $char = substr($possible, mt_rand(0, $maxlength-1), 1);
            if (!strstr($password, $char)) { 
                $password .= $char;
                $i++;
            }
        }

        return $password;
    }

    public function decodeEmailCode ($code, $rep) {
        $s1 = substr($code, 0, 1);
        $s2 = substr($code, $s1 + 1, 1);

        $id_len = substr($code, $s1 + 1 + $s2 + 1, 1);
        $id = substr($code, $s1 + 1 + $s2 + 1 + 1, $id_len);

        $id_sum = array_sum(str_split($id)); 

        $subscribe = $rep->findOneById($id);

        if (!$subscribe)
            return;

        if (!$subscribe->getEmail())
            return;

        $ut = $subscribe->getCreatedAt('epoch');

        $k = substr($code, $id_sum, 1);

        if ($k < 2)
            $k = 2;
        if ($k > 5)
            $k = 5;

        $k *= -1;

        $ut_part = substr($ut, $k);   
        $ut_sum  = array_sum(str_split($ut_part)); 

        $get_sum_code = substr(
            $code, 
            $s1 + 1 + $s2 + 1 + 1 + $id_len,
            strlen($ut_sum)
        );

        if ($ut_sum == $get_sum_code) {
            return $id;
        } else {
            return;
        }
    }

}
