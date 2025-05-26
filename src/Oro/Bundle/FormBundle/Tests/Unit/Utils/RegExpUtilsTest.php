<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Utils;

use Oro\Bundle\FormBundle\Utils\RegExpUtils;
use PHPUnit\Framework\TestCase;

class RegExpUtilsTest extends TestCase
{
    public function testValidateRegExpWhenInvalid(): void
    {
        self::assertEquals('preg_match(): Unknown modifier \'t\'', RegExpUtils::validateRegExp('/invalid/test/'));
    }

    public function testValidateRegExpWhenValid(): void
    {
        self::assertNull(RegExpUtils::validateRegExp('/^(valid\sregexp)$/'));
    }
}
