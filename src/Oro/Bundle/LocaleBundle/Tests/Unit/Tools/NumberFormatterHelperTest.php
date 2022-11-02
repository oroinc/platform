<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Tools;

use NumberFormatter as IntlNumberFormatter;
use Oro\Bundle\LocaleBundle\Tools\NumberFormatterHelper;

class NumberFormatterHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider parseConstantValueDataProvider
     */
    public function testParseConstantValue($name): void
    {
        $this->assertEquals(
            IntlNumberFormatter::CURRENCY,
            NumberFormatterHelper::parseConstantValue($name)
        );
    }

    public function parseConstantValueDataProvider(): array
    {
        return [
            ['currency'],
            ['CuRrEnCy'],
            [IntlNumberFormatter::CURRENCY],
        ];
    }

    public function testParseConstantValueWhenInvalidConstant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NumberFormatter has no constant \'invalid\'');

        NumberFormatterHelper::parseConstantValue('invalid');
    }
}
