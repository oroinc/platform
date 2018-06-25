<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class FormTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider normalizeValueProvider
     * @param mixed $expectedValue
     * @param mixed $actualValue
     */
    public function testNormalizeValue($expectedValue, $actualValue)
    {
        $normalizedValue = Form::normalizeValue($actualValue);

        // because assertSame will check that two arguments has links to the same object
        if (is_object($expectedValue)) {
            self::assertEquals($expectedValue, $normalizedValue);
        } else {
            self::assertSame($expectedValue, $normalizedValue);
        }
    }

    /**
     * @return array
     */
    public function normalizeValueProvider()
    {
        $dateTime = new \DateTime('2017-05-22');
        return [
            [
                'expected' => ['1', '2', '3'],
                'actual' => '[1, 2, 3]',
            ],
            [
                'expected' => $dateTime,
                'actual' => '2017-05-22',
            ],
            [
                'expected' => [true, false, 'yes', 'no', 'on', 'off', 'checked', 'unchecked'],
                'actual' => '[true, false, yes, no, on, off, checked, unchecked]',
            ],
            [
                'expected' => 'Daily every 5 days, end by May 22, 2017',
                'actual' => 'Daily every 5 days, end by <Date:2017-05-22>',
            ],
            [
                'expected' => $dateTime,
                'actual' => '<DateTime:2017-05-22>',
            ],
        ];
    }
}
