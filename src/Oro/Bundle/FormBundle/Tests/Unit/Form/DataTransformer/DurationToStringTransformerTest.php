<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\DurationToStringTransformer;

class DurationToStringTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider transformDataProvider
     *
     * @param mixed $value
     * @param mixed $expectedValue
     */
    public function testTransform($value, $expectedValue)
    {
        $transformer = $this->createTestTransformer();
        $this->assertEquals($expectedValue, $transformer->transform($value));
    }

    public function transformDataProvider()
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

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "scalar", "array" given
     */
    public function testTransformFailsWhenUnexpectedType()
    {
        $transformer = $this->createTestTransformer();
        $transformer->transform([]);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Duration too long to convert.
     */
    public function testTransformFailsWithBigNumbers()
    {
        $transformer = $this->createTestTransformer();
        $transformer->transform(PHP_INT_MAX);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param mixed $value
     * @param mixed $expectedValue
     */
    public function testReverseTransform($value, $expectedValue)
    {
        $transformer = $this->createTestTransformer();
        $transformed = $transformer->reverseTransform($value);
        $this->assertEquals($expectedValue, $transformed);
    }

    public function reverseTransformDataProvider()
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
                3783, // '01:03:03' (minutes are rounded up)
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
                '1.5h 2.5m 3.5s',
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
     *
     * @param mixed $value
     */
    public function testReverseTransformReturnsNullWhenEmpty($value)
    {
        $transform = $this->createTestTransformer()->reverseTransform($value);
        $this->assertNull($transform);
    }

    public function reverseTransformEmptyDataProvider()
    {
        return [
            'empty string' => [''],
            'whitespaces' => [' '],
            'null' => [null],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     */
    public function testReverseTransformFailsWhenUnexpectedType()
    {
        $this->createTestTransformer()->reverseTransform(array());
    }

    private function createTestTransformer()
    {
        return new DurationToStringTransformer();
    }
}
