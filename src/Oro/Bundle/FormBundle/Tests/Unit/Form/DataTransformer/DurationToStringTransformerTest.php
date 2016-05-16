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
            'default' => [
                \DateTime::createFromFormat('U', 120),
                '00:02:00',
            ],
            'string' => [
                '120',
                '120',
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Expected a \DateTime or \DateTimeInterface.
     */
    public function testTransformFailsWhenUnexpectedType()
    {
        $transformer = $this->createTestTransformer();
        $transformer->transform([]);
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
        $this->assertInstanceOf('DateTime', $transformed);
        $this->assertEquals($expectedValue, $transformed->format('H:i:s'));
    }

    public function reverseTransformDataProvider()
    {
        return [
            'default' => [
                '1:2:3',
                '01:02:03',
            ],
            'Column style no hours' => [
                '2:3',
                '00:02:03',
            ],
            'Column style only seconds' => [
                '3',
                '00:00:03',
            ],
            'Column style 123 seconds' => [
                '123',
                '00:02:03',
            ],
            'Column style 1:123 seconds' => [
                '1:123',
                '00:03:03',
            ],
            'Column style untrimmed' => [
                ' 1:2 : 3  ',
                '01:02:03',
            ],
            'Column style extra columns' => [
                '1:2:3::4',
                '01:02:03',
            ],
            'Column style extra trailing symbols' => [
                '1a:2.5:3c',
                '01:02:03',
            ],
            'Column style extra leading symbols' => [
                'a1:2b:3c',
                '00:02:03',
            ],
            'JIRA style' => [
                '1h 2m 3s',
                '01:02:03',
            ],
            'JIRA style fractions rounded' => [
                '1.5h 2.5m 3.5s',
                '01:32:34',
            ],
            'JIRA style hours' => [
                '1h',
                '01:00:00',
            ],
            'JIRA style 23h' => [
                '23h',
                '23:00:00',
            ],
            // TODO: fix time field and remove the test
            'JIRA style 24h trimmed to 00' => [
                '24h',
                '00:00:00',
            ],
            'JIRA style minutes' => [
                '2m',
                '00:02:00',
            ],
            'JIRA style seconds' => [
                '3s',
                '00:00:03',
            ],
            'JIRA style hours minutes' => [
                '1h 2m',
                '01:02:00',
            ],
            'JIRA style minutes seconds' => [
                '2m 3s',
                '00:02:03',
            ],
            'JIRA style hours seconds' => [
                '1h 3s',
                '01:00:03',
            ],
            'JIRA style no spaces' => [
                '1h2m3s',
                '01:02:03',
            ],
            'JIRA style no spaces fractions' => [
                '1.5h2.5m3.5s',
                '01:32:34',
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


    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Failed to create a \DateTime instance.
     */
    public function testReverseTransformFailsWithBigNumbers()
    {
        $this->createTestTransformer()
             ->reverseTransform(
                 sprintf('%s:%s:%s', PHP_INT_MAX, PHP_INT_MAX, PHP_INT_MAX)
             )
        ;
    }

    private function createTestTransformer()
    {
        return new DurationToStringTransformer();
    }
}
