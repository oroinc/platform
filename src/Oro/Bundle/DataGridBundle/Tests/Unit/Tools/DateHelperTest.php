<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Tools;

use Oro\Bundle\DataGridBundle\Tools\DateHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class DateHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|LocaleSettings */
    private $localeSettings;

    /** @var DateHelper */
    private $dateHelper;

    protected function setUp()
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->dateHelper = new DateHelper($this->localeSettings);
    }

    private function getTimeZoneOffset($timeZone)
    {
        return (new \DateTime('now', new \DateTimeZone($timeZone)))->format('P');
    }

    public function testGetTimeZoneOffset()
    {
        $this->localeSettings->expects(self::once())
            ->method('getTimeZone')
            ->willReturn('Europe/Kiev');

        $offset = $this->getTimeZoneOffset('Europe/Kiev');
        self::assertEquals($offset, $this->dateHelper->getTimeZoneOffset());
        // test that the offset is cached
        self::assertEquals($offset, $this->dateHelper->getTimeZoneOffset());
    }

    public function testGetTimeZoneOffsetForUTC()
    {
        $this->localeSettings->expects(self::once())
            ->method('getTimeZone')
            ->willReturn('UTC');

        self::assertEquals('+00:00', $this->dateHelper->getTimeZoneOffset());
    }

    public function testGetConvertTimezoneExpression()
    {
        $this->localeSettings->expects(self::once())
            ->method('getTimeZone')
            ->willReturn('Europe/Kiev');

        $offset = $this->getTimeZoneOffset('Europe/Kiev');
        self::assertEquals(
            sprintf('CONVERT_TZ(e.createdAt, \'+00:00\', \'%s\')', $offset),
            $this->dateHelper->getConvertTimezoneExpression('e.createdAt')
        );
    }

    public function testGetConvertTimezoneExpressionForUTC()
    {
        $this->localeSettings->expects(self::once())
            ->method('getTimeZone')
            ->willReturn('UTC');

        self::assertEquals(
            'e.createdAt',
            $this->dateHelper->getConvertTimezoneExpression('e.createdAt')
        );
    }
}
