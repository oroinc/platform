<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Formatter;

use Oro\Bundle\SearchBundle\Formatter\DateTimeFormatter;

class DateTimeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DateTimeFormatter
     */
    protected $dateTimeFormatter;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->dateTimeFormatter = new DateTimeFormatter();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        unset($this->dateTimeFormatter);
    }

    /**
     * @dataProvider formatProvider
     *
     * @param string $dateTimeString
     * @param string $dateTimeZone
     * @param string $expectedDateTimeString
     */
    public function testFormat($dateTimeString, $dateTimeZone, $expectedDateTimeString)
    {
        $dateTime = new \DateTime($dateTimeString, new \DateTimeZone($dateTimeZone));
        $this->assertEquals(
            $expectedDateTimeString,
            $this->dateTimeFormatter->format($dateTime)
        );
    }

    public function formatProvider()
    {
        return [
            [
                'dateTimeString' => '2017-08-21 00:00:00',
                'dateTimeZone' =>   'UTC',
                'expectedDateTimeString' => '2017-08-21 00:00:00'
            ],
            [
                'dateTimeString' => '2017-08-21 00:00:00',
                'dateTimeZone' =>   'Europe/Berlin',
                'expectedDateTimeString' => '2017-08-20 22:00:00'
            ]
        ];
    }
}
