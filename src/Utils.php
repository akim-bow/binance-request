<?php

namespace BinanceRequest;

class Utils
{
    public static function arrayFind(array $array, callable $callback) {
        foreach ($array as $key => $value) {
            $result = $callback($value, $key);

            if ($result) {
                return $value;
            }
        }

        return null;
    }
}