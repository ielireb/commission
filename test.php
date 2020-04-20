<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require 'commission.php';

class Test extends TestCase
{
    private $Commission;
 
    protected function setUp() : void
    {
        parent::setUp();
        $this->Commission = new Commission();
    }

    public function tearDown() : void
    {
        $this->Commission = null;
    }

    public function testHandleFileDoesNotExist()
    {
        $fileName = 'non-existing-file.txt';
        $this->expectException('Exception');
        $this->expectExceptionMessage('File open failed. - '.$fileName);
        $result = $this->Commission->handleFile($fileName);
    }

    public function testHandleFileExists()
    {
        $fileName = 'input.txt';
        $result = $this->Commission->handleFile($fileName);
        $this->assertNotEmpty($result);
    }

    public function testIsEu()
    {
        $currency = 'AT';
        $result = $this->Commission->isEu($currency);
        $this->assertTrue($result);
    }

    public function testIsNotEu()
    {
        $currency = 'GBP';
        $result = $this->Commission->isEu($currency);
        $this->assertFalse($result);
    }

    public function testHandleLineCorrectData()
    {
        $ret = $this->Commission->handleFile('https://api.exchangeratesapi.io/latest');
        $rates = $this->Commission->processData($ret, [0, 'rates']);
        $this->assertNotEmpty($rates);

        $line = '{"bin":"45717360","amount":"100.00","currency":"EUR"}';
        $result = $this->Commission->handleLine($line, $rates);
        $this->assertEquals($result, 1);

        $line = '{"bin":"516793","amount":"50.00","currency":"USD"}';
        $result = $this->Commission->handleLine($line, $rates);
        $this->assertEquals($result, 0.46);

        $line = '{"bin":"45417360","amount":"10000.00","currency":"JPY"}';
        $result = $this->Commission->handleLine($line, $rates);
        $this->assertEquals($result, 1.71);

        $line = '{"bin":"41417360","amount":"130.00","currency":"USD"}';
        $result = $this->Commission->handleLine($line, $rates);
        $this->assertEquals($result, 2.39);

        $line = '{"bin":"4745030","amount":"2000.00","currency":"GBP"}';
        $result = $this->Commission->handleLine($line, $rates);
        $this->assertEquals($result, 45.99);
    }

    public function testHandleLineWrongData()
    {
        $ret = $this->Commission->handleFile('https://api.exchangeratesapi.io/latest');
        $rates = $this->Commission->processData($ret, [0, 'rates']);
        $this->assertNotEmpty($rates);

        $line = '{"bin1":"4745030","amount":"2000.00","currency":"GBP"}';
        $this->expectException('Exception');
        $this->expectExceptionMessage('Bin is not set.');
        $result = $this->Commission->handleLine($line, $rates);

        $line = '{"bin":"4745030","amount1":"2000.00","currency":"GBP"}';
        $this->expectException('Exception');
        $this->expectExceptionMessage('Amount is not set.');
        $result = $this->Commission->handleLine($line, $rates);

        $line = '{"bin":"4745030","amount":"2000.00","currency1":"GBP"}';
        $this->expectException('Exception');
        $this->expectExceptionMessage('Currency is not set.');
        $result = $this->Commission->handleLine($line, $rates);

        $line = 'sddsdsff';
        $this->expectException('Exception');
        $this->expectExceptionMessage('Bin is not set.');
        $result = $this->Commission->handleLine($line, $rates);
    }
}