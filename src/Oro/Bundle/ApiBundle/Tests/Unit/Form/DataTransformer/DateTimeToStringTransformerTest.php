<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateTimeToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function testTransformWithNullValue()
    {
        $transformer = new DateTimeToStringTransformer();
        self::assertSame('', $transformer->transform(null));
    }

    public function testTransformWithNotDateTimeValue()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a \DateTimeInterface.');

        $transformer = new DateTimeToStringTransformer();
        $transformer->transform(123);
    }

    /**
     * @dataProvider validValueForTransformDataProvider
     */
    public function testTransformWithValidValue($value, $expected)
    {
        $transformer = new DateTimeToStringTransformer();
        self::assertEquals($expected, $transformer->transform(new \DateTime($value)));
    }

    public function validValueForTransformDataProvider()
    {
        return [
            'zero milliseconds with timezone'         => [
                '2017-07-21T10:20:30.000+05:00',
                '2017-07-21T10:20:30+05:00'
            ],
            'not zero milliseconds with timezone'     => [
                '2017-07-21T10:20:30.123+05:00',
                '2017-07-21T10:20:30.123+05:00'
            ],
            'zero milliseconds with UTC timezone'     => [
                '2017-07-21T10:20:30.000+00:00',
                '2017-07-21T10:20:30Z'
            ],
            'not zero milliseconds with UTC timezone' => [
                '2017-07-21T10:20:30.123+00:00',
                '2017-07-21T10:20:30.123Z'
            ]
        ];
    }

    /**
     * @dataProvider validValueForTransformDateDataProvider
     */
    public function testTransformDateWithValidValue($value, $expected)
    {
        $transformer = new DateTimeToStringTransformer(false);
        self::assertEquals($expected, $transformer->transform(new \DateTime($value)));
    }

    public function validValueForTransformDateDataProvider()
    {
        return [
            'with timezone'     => [
                '2017-07-21T10:20:30+05:00',
                '2017-07-21'
            ],
            'with UTC timezone' => [
                '2017-07-21T10:20:30+00:00',
                '2017-07-21'
            ]
        ];
    }

    /**
     * @dataProvider validValueForTransformTimeDataProvider
     */
    public function testTransformTimeWithValidValue($value, $expected)
    {
        $transformer = new DateTimeToStringTransformer(true, false);
        self::assertEquals($expected, $transformer->transform(new \DateTime($value)));
    }

    public function validValueForTransformTimeDataProvider()
    {
        return [
            'zero milliseconds with timezone'         => [
                '1970-01-01T10:20:30.000+05:00',
                '10:20:30'
            ],
            'not zero milliseconds with timezone'     => [
                '1970-01-01T10:20:30.123+05:00',
                '10:20:30'
            ],
            'zero milliseconds with UTC timezone'     => [
                '1970-01-01T10:20:30.000+00:00',
                '10:20:30'
            ],
            'not zero milliseconds with UTC timezone' => [
                '1970-01-01T10:20:30.123+00:00',
                '10:20:30'
            ]
        ];
    }

    public function testReverseTransformWithEmptyString()
    {
        $transformer = new DateTimeToStringTransformer();
        self::assertNull($transformer->reverseTransform(''));
    }

    /**
     * @dataProvider validValueForReverseTransformDataProvider
     */
    public function testReverseTransformWithValidValue($value, $expected)
    {
        $transformer = new DateTimeToStringTransformer();
        self::assertEquals($expected, $transformer->reverseTransform($value)->format('Y-m-d\TH:i:s.vO'));
    }

    public function validValueForReverseTransformDataProvider()
    {
        return [
            'year only'                              => [
                '2017',
                '2017-01-01T00:00:00.000+0000'
            ],
            'year and month only'                    => [
                '2017-07',
                '2017-07-01T00:00:00.000+0000'
            ],
            'date only'                              => [
                '2017-07-21',
                '2017-07-21T00:00:00.000+0000'
            ],
            'with timezone'                          => [
                '2017-07-21T10:20:30+05:00',
                '2017-07-21T05:20:30.000+0000'
            ],
            'with UTC timezone'                      => [
                '2017-07-21T10:20:30Z',
                '2017-07-21T10:20:30.000+0000'
            ],
            'without seconds, with timezone'         => [
                '2017-07-21T10:20+05:00',
                '2017-07-21T05:20:00.000+0000'
            ],
            'without seconds, with UTC timezone'     => [
                '2017-07-21T10:20Z',
                '2017-07-21T10:20:00.000+0000'
            ],
            'with milliseconds and timezone'         => [
                '2017-07-21T10:20:30.123+05:00',
                '2017-07-21T05:20:30.123+0000'
            ],
            'with milliseconds and UTC timezone'     => [
                '2017-07-21T10:20:30.123Z',
                '2017-07-21T10:20:30.123+0000'
            ],
            'max time'                               => [
                '2017-07-21T23:59:59Z',
                '2017-07-21T23:59:59.000+0000'
            ],
            'year and time with timezone'            => [
                '2017T10:20:30+05:00',
                '2017-01-01T05:20:30.000+0000'
            ],
            'year, month and time with timezone'     => [
                '2017-07T10:20:30+05:00',
                '2017-07-01T05:20:30.000+0000'
            ],
            'year and time with UTC timezone'        => [
                '2017T10:20:30Z',
                '2017-01-01T10:20:30.000+0000'
            ],
            'year, month and time with UTC timezone' => [
                '2017-07T10:20:30Z',
                '2017-07-01T10:20:30.000+0000'
            ],
            'max value'                              => [
                '9999-12-31T23:59:59.999Z',
                '9999-12-31T23:59:59.999+0000'
            ],
            'min value'                              => [
                '0001-01-01T00:00:00.000Z',
                '0001-01-01T00:00:00.000+0000'
            ]
        ];
    }

    /**
     * @dataProvider invalidValueForReverseTransformDataProvider
     */
    public function testReverseTransformWithInvalidValue($value, $exceptionMessage = null)
    {
        if (null === $exceptionMessage) {
            $exceptionMessage = sprintf('The value "%s" is not a valid datetime.', $value);
        }
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $transformer = new DateTimeToStringTransformer();
        $transformer->reverseTransform($value);
    }

    public function invalidValueForReverseTransformDataProvider()
    {
        return [
            'without timezone'                        => [
                '2017-07-21T10:20:30'
            ],
            'without seconds and timezone'            => [
                '2017-07-21T10:20'
            ],
            'with milliseconds, but without timezone' => [
                '2017-07-21T10:20:30.123'
            ],
            'without minutes'                         => [
                '2017-07-21T10Z'
            ],
            'invalid time delimiter'                  => [
                '2017-07-21 10:20:30'
            ],
            'invalid date'                            => [
                '2017-02-30T10:20:30Z',
                'The date "2017-02-30" is not a valid date.'
            ],
            'out of bounds for years (max)'           => [
                '10000-01-01T00:00:00Z'
            ],
            'out of bounds for years (min)'           => [
                '0000-01-01T00:00:00Z',
                'The date "0000-01-01" is not a valid date.'
            ],
            'out of bounds for months (max)'          => [
                '2017-13-21T10:20:30Z',
                'The date "2017-13-21" is not a valid date.'
            ],
            'out of bounds for months (min)'          => [
                '2017-00-21T10:20:30Z',
                'The date "2017-00-21" is not a valid date.'
            ],
            'out of bounds for days (max)'            => [
                '2017-07-32T10:20:30Z',
                'The date "2017-07-32" is not a valid date.'
            ],
            'out of bounds for days (min)'            => [
                '2017-07-00T10:20:30Z',
                'The date "2017-07-00" is not a valid date.'
            ],
            'out of bounds for hours'                 => [
                '2017-07-21T24:20:30Z',
                'The time "24:20:30" is not a valid time.'
            ],
            'out of bounds for minutes'               => [
                '2017-07-21T10:60:30Z',
                'The time "10:60:30" is not a valid time.'
            ],
            'out of bounds for seconds'               => [
                '2017-07-21T10:20:60Z',
                'The time "10:20:60" is not a valid time.'
            ],
            'without leading zero in months'          => [
                '2017-7-21T10:20:30Z'
            ],
            'without leading zero in days'            => [
                '2017-07-1T10:20:30Z'
            ],
            'without leading zero in hours'           => [
                '2017-07-21T1:20:30Z'
            ],
            'without leading zero in minutes'         => [
                '2017-07-21T10:1:30Z'
            ],
            'without leading zero in seconds'         => [
                '2017-07-21T10:20:1Z'
            ]
        ];
    }

    /**
     * @dataProvider validValueForReverseTransformDateDataProvider
     */
    public function testReverseTransformDateWithValidValue($value, $expected)
    {
        $transformer = new DateTimeToStringTransformer(false);
        self::assertEquals($expected, $transformer->reverseTransform($value)->format('Y-m-d\TH:i:s.vO'));
    }

    public function validValueForReverseTransformDateDataProvider()
    {
        return [
            'full date'           => [
                '2017-07-21',
                '2017-07-21T00:00:00.000+0000'
            ],
            'year only'           => [
                '2017',
                '2017-01-01T00:00:00.000+0000'
            ],
            'year and month only' => [
                '2017-07',
                '2017-07-01T00:00:00.000+0000'
            ],
            'max value'           => [
                '9999-12-31',
                '9999-12-31T00:00:00.000+0000'
            ],
            'min value'           => [
                '0001-01-01',
                '0001-01-01T00:00:00.000+0000'
            ]
        ];
    }

    /**
     * @dataProvider invalidValueForReverseTransformDateDataProvider
     */
    public function testReverseTransformDateWithInvalidValue($value, $exceptionMessage = null)
    {
        if (null === $exceptionMessage) {
            $exceptionMessage = sprintf('The value "%s" is not a valid date.', $value);
        }
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $transformer = new DateTimeToStringTransformer(false);
        $transformer->reverseTransform($value);
    }

    public function invalidValueForReverseTransformDateDataProvider()
    {
        return [
            'with time'                      => [
                '2017-07-21T00:00:00'
            ],
            'with time and timezone'         => [
                '2017-07-21T00:00:00+05:00'
            ],
            'with time and UTC timezone'     => [
                '2017-07-21T00:00:00Z'
            ],
            'invalid date'                   => [
                '2017-02-30',
                'The date "2017-02-30" is not a valid date.'
            ],
            'out of bounds for years (max)'  => [
                '10000-01-01'
            ],
            'out of bounds for years (min)'  => [
                '0000-01-01',
                'The date "0000-01-01" is not a valid date.'
            ],
            'out of bounds for months (max)' => [
                '2017-13-21',
                'The date "2017-13-21" is not a valid date.'
            ],
            'out of bounds for months (min)' => [
                '2017-00-21',
                'The date "2017-00-21" is not a valid date.'
            ],
            'out of bounds for days (max)'   => [
                '2017-07-32',
                'The date "2017-07-32" is not a valid date.'
            ],
            'out of bounds for days (min)'   => [
                '2017-07-00',
                'The date "2017-07-00" is not a valid date.'
            ],
            'without leading zero in months' => [
                '2017-7-21'
            ],
            'without leading zero in days'   => [
                '2017-07-1'
            ]
        ];
    }

    /**
     * @dataProvider validValueForReverseTransformTimeDataProvider
     */
    public function testReverseTransformTimeWithValidValue($value, $expected)
    {
        $transformer = new DateTimeToStringTransformer(true, false);
        self::assertEquals($expected, $transformer->reverseTransform($value)->format('Y-m-d\TH:i:s.vO'));
    }

    public function validValueForReverseTransformTimeDataProvider()
    {
        return [
            'full time'            => [
                '10:20:30',
                '1970-01-01T10:20:30.000+0000'
            ],
            'without seconds'      => [
                '10:20',
                '1970-01-01T10:20:00.000+0000'
            ],
            'max time'             => [
                '23:59:59',
                '1970-01-01T23:59:59.000+0000'
            ],
            'without leading zero' => [
                '1:2:3',
                '1970-01-01T01:02:03.000+0000'
            ],
            'max value'            => [
                '23:59:59',
                '1970-01-01T23:59:59.000+0000'
            ],
            'min value'            => [
                '00:00:00',
                '1970-01-01T00:00:00.000+0000'
            ]
        ];
    }

    /**
     * @dataProvider invalidValueForReverseTransformTimeDataProvider
     */
    public function testReverseTransformTimeWithInvalidValue($value, $exceptionMessage = null)
    {
        if (null === $exceptionMessage) {
            $exceptionMessage = sprintf('The value "%s" is not a valid time.', $value);
        }
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $transformer = new DateTimeToStringTransformer(true, false);
        $transformer->reverseTransform($value);
    }

    public function invalidValueForReverseTransformTimeDataProvider()
    {
        return [
            'with date'                 => [
                '2017-07-21T10:20:30Z'
            ],
            'with timezone'             => [
                '10:20:30+05:00'
            ],
            'with UTC timezone'         => [
                '10:20:30Z'
            ],
            'without minutes'           => [
                '10'
            ],
            'out of bounds for hours'   => [
                '24:20:30',
                'The time "24:20:30" is not a valid time.'
            ],
            'out of bounds for minutes' => [
                '10:60:30',
                'The time "10:60:30" is not a valid time.'
            ],
            'out of bounds for seconds' => [
                '10:20:60',
                'The time "10:20:60" is not a valid time.'
            ]
        ];
    }
}
