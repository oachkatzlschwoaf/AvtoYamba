<?php

namespace AY\GeneralBundle\Entity;

class Util {

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

}
