<?php
require 'commission.php';

$res = [
    'success' => true,
    'errors' => []
];

if ($argc > 1) {
    try {
        $Commission = new Commission();
        $line = $Commission->handleFile($argv[1]);

        $ret = $Commission->handleFile('https://api.exchangeratesapi.io/latest');
        $rates = $Commission->processData($ret, [0, 'rates']);

        foreach($line as $l) {
            echo $Commission->handleLine($l, $rates) . PHP_EOL;
        }
    } catch (Exception $e) {
        $res['success'] = false;
        $res['errors'][] = $e->getMessage();
        print_r($res);
    }
}