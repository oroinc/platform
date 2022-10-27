<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\FormBundle\Form\DataTransformer\DurationToStringTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class DurationToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    private DurationToStringTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DurationToStringTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(int|float $value, string $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        return [
            '120 seconds' => [
                120,
                '2m',
            ],
            '1 day and 10 seconds' => [
                86410, // 3600 * 24 + 10,
                '24h 10s',
            ],
            '120.5 seconds round up' => [
                120.5,
                '2m 1s',
            ],
        ];
    }

    public function testTransformFailsWhenUnexpectedType()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "scalar", "array" given');

        $this->transformer->transform([]);
    }

    public function testTransformFailsWithBigNumbers()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Duration too long to convert.');

        $this->transformer->transform(PHP_INT_MAX);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(string $value, int $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            'default' => [
                '1:2:3',
                3723, // '01:02:03'
            ],
            'Column style no hours' => [
                '2:3',
                123, // '00:02:03'
            ],
            'Column style only seconds' => [
                '3',
                3,
            ],
            'Column style seconds round up' => [
                '3.5',
                4,
            ],
            'Column style seconds round down' => [
                '3.4',
                3,
            ],
            'Column style 123 seconds' => [
                '123',
                123, // '00:02:03'
            ],
            'Column style 1:123 seconds' => [
                '1:123',
                183, // '00:03:03'
            ],
            'Column style untrimmed' => [
                ' 1:2 : 3  ',
                3723, // '01:02:03'
            ],
            'Column style extra columns' => [
                '1:2:3::4',
                3723, // '01:02:03'
            ],
            'Column style extra trailing symbols' => [
                '1a:2.5:3c',
                3753, // '01:02:33'
            ],
            'Column style extra leading symbols' => [
                'a1:2b:3c',
                123, // '00:02:03'
            ],
            'JIRA style all parts' => [
                '1h 2m 3s',
                3723, // '01:02:03'
            ],
            'JIRA style all parts with fractions' => [
                '1.5h 2.25m 3.5s',
                5539, // '01:32:19' rounded
            ],
            'JIRA style all parts with comma fractions' => [
                '1,5h 2.5m 3,5s',
                5554, // '01:32:34' rounded
            ],
            'JIRA style no spaces fractions' => [
                '1.5h2.5m3.5s',
                5554, // '01:32:34' rounded
            ],
            'JIRA style only hours' => [
                '24h',
                86400, // '24:00:00'
            ],
            'JIRA style only minutes' => [
                '90m',
                5400, // '01:30:00'
            ],
            'JIRA style only seconds' => [
                '120s',
                120, // '00:02:00'
            ],
            'JIRA style hours minutes' => [
                '1h 90m',
                9000, // '2:30:00'
            ],
            'JIRA style minutes seconds' => [
                '90m 120s',
                5520, // '2:32:00'
            ],
            'JIRA style hours seconds' => [
                '1h 3s',
                3603, // '01:00:03'
            ],
            'JIRA style no spaces' => [
                '1h2m3s',
                3723, // '01:02:03'
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformEmptyDataProvider
     */
    public function testReverseTransformReturnsNullWhenEmpty(?string $value)
    {
        $this->assertNull($this->transformer->reverseTransform($value));
    }

    public function reverseTransformEmptyDataProvider(): array
    {
        return [
            'empty string' => [''],
            'whitespaces' => [' '],
            'null' => [null],
        ];
    }

    public function testReverseTransformFailsWhenUnexpectedType()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->transformer->reverseTransform([]);
    }
}
