<?php
class Commission
{
    protected function isJson($string) {
        @json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }


    function handleFile($fileName)
    {
        $content = @file_get_contents($fileName);
        if($content === FALSE) {
            throw new Exception('File open failed. - '.$fileName);
        }
        $line = explode("\n", $content);
        return $line;
    }

    function isEu($c)
    {
        return in_array($c, ['AT','BE','BG','CY','CZ','DE','DK','EE','ES','FI','FR','GR','HR','HU','IE','IT','LT','LU','LV','MT','NL','PO','PT','RO','SE','SI','SK']);
    }

    function handleLine($line, $rates)
    {
        if (empty($line)) {
            throw new Exception('Line is empty.');
        }

        $val = self::processData($line);

        if (!isset($val->bin)) {
            throw new Exception('Bin is not set.');
        }
        if (!isset($val->currency)) {
            throw new Exception('Currency is not set.');
        }
        if (!isset($val->amount)) {
            throw new Exception('Amount is not set.');
        }

        $ret = self::handleFile('https://lookup.binlist.net/'.$val->bin);
        $alpha2 = self::processData($ret, [0, 'country', 'alpha2']);
        
        $amountFixed = 0;
        if ($val->amount == 'EUR' || @$rates->{$val->currency} == 0) {
            $amountFixed = $val->amount;
        }
        if ($val->currency !== 'EUR' || @$rates->{$val->currency} > 0) {
            $amountFixed = $val->amount / @$rates->{$val->currency};
        }
        return round($amountFixed * (self::isEu($alpha2) ? 0.01 : 0.02), 2);
    }

    function processData($data, $keys = [], $i = 0)
    {
        if (!isset($data)) {
            throw new Exception('Data is not set. - '. $keys[$i]);
        }
        if (!self::isJson($data)) {
            throw new Exception('Data is not in json format.');
        }
        $decoded = is_array($data) ? (object) $data : ( is_object($data) ? $data : json_decode($data) );
        $decoded = $decoded;

        if ($i >= count($keys)-1) return empty($keys) ? $decoded : $decoded->{$keys[$i]};

        return self::processData($decoded->{$keys[$i]}, $keys, ++$i);
    }
}