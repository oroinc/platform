<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Formatter;

use Oro\Bundle\SearchBundle\Formatter\DateTimeFormatter;
use Oro\Component\Exception\UnexpectedTypeException;

class DateTimeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeFormatter */
    private $dateTimeFormatter;

    protected function setUp(): void
    {
        $this->dateTimeFormatter = new DateTimeFormatter();
    }

    /**
     * @dataProvider formatProvider
     */
    public function testFormat(string $dateTimeString, string $dateTimeZone, string $expectedDateTimeString)
    {
        $dateTime = new \DateTime($dateTimeString, new \DateTimeZone($dateTimeZone));
        $this->assertEquals(
            $expectedDateTimeString,
            $this->dateTimeFormatter->format($dateTime)
        );
    }

    public function formatProvider(): array
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

    public function testFormatInvalidValue()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "\DateTime", "string" given');

        $this->dateTimeFormatter->format('2017-08-21 00:00:00');
    }
}
