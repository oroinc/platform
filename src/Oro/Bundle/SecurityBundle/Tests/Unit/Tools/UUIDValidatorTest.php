<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Tools;

use Oro\Bundle\SecurityBundle\Tools\UUIDValidator;
use PHPUnit\Framework\TestCase;

class UUIDValidatorTest extends TestCase
{
    public function testIsValidV4(): void
    {
        self::assertTrue(UUIDValidator::isValidV4('e9ff6eea-9422-4689-ab69-ee2567103cd1'));
        self::assertFalse(UUIDValidator::isValidV4(''));
        self::assertFalse(UUIDValidator::isValidV4('e9ff6eea-9422-4689-ab69-ee2567103cd1!'));
        self::assertFalse(UUIDValidator::isValidV4('111'));
    }
}
