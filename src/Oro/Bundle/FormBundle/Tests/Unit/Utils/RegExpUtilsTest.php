<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Utils;

use Oro\Bundle\FormBundle\Utils\RegExpUtils;

class RegExpUtilsTest extends \PHPUnit\Framework\TestCase
{
    public function testValidateRegExpWhenInvalid(): void
    {
        self::assertEquals('preg_match(): Unknown modifier \'r\'', RegExpUtils::validateRegExp('/invalid/regexp/'));
    }

    public function testValidateRegExpWhenValid(): void
    {
        self::assertNull(RegExpUtils::validateRegExp('/^(valid\sregexp)$/'));
    }
}
