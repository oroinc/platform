<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\Percent100ToLocalizedStringTransformer;

class Percent100ToLocalizedStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    private static function assertFloatSame(?float $expected, ?float $actual): void
    {
        /**
         * do rough assertion first to show user friendly message when the assertion failed
         */
        self::assertSame($expected, $actual);

        /**
         * do precise assertion;
         * it is required due to {@see \PHPUnit\Framework\Constraint\IsIdentical::EPSILON}
         */
        if (is_float($expected) && is_float($actual)) {
            /** @noinspection PhpUnitTestsInspection */
            self::assertTrue(
                $expected === $actual,
                sprintf(
                    'Failed asserting that %s matches expected %s. Delta: %s.',
                    $actual,
                    $expected,
                    $expected - $actual
                )
            );
        }
    }

    public function transformDataProvider(): array
    {
        return [
            [null, ''],
            [1.0, '0.01'],
            [1.5, '0.015'],
            [111, '1.11'],
            [1.234000000001, '0.01234000000001'],
            [1.2340000000001, '0.01234'],
            [1.2340000000009, '0.01234000000001']
        ];
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            ['', null],
            ['0.01', 1.0],
            ['0.015', 1.5],
            ['1.11', 111.0],
            ['0.01234000000001', 1.234000000001],
            ['0.012340000000001', 1.234],
            ['0.012340000000009', 1.234000000001]
        ];
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(?float $from, string $to): void
    {
        $transformer = new Percent100ToLocalizedStringTransformer();
        self::assertSame($to, $transformer->transform($from));
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(string $from, ?float $to): void
    {
        $transformer = new Percent100ToLocalizedStringTransformer();
        self::assertFloatSame($to, $transformer->reverseTransform($from));
    }
}
