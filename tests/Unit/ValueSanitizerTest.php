<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PlacetoPay\GuzzleLogger\ValueSanitizer;

class ValueSanitizerTest extends TestCase
{
    public function test_card_number_sanitization(): void
    {
        $this->assertEquals('1***', ValueSanitizer::cardNumber('1234'));
        $this->assertEquals('1******8', ValueSanitizer::cardNumber('12345678'));
        $this->assertEquals('411111******1111', ValueSanitizer::cardNumber('4111111111111111'));
    }
}