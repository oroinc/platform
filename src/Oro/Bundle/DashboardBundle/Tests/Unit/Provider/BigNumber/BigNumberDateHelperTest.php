<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\BigNumber;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\Provider\BigNumber\BigNumberDateHelper;
use Oro\Bundle\LocaleBundle\Model\Calendar;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class BigNumberDateHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @dataProvider localeDataProvider
     */
    public function testGetLastWeekPeriodForLocale(string $locale, string $timezone, string $expectedWeekStart)
    {
        $calendar = new Calendar($locale);

        $localeSettings = $this->createMock(LocaleSettings::class);
        $localeSettings->expects($this->any())
            ->method('getTimeZone')
            ->willReturn($timezone);
        $localeSettings->expects($this->any())
            ->method('getCalendar')
            ->willReturn($calendar);

        $helper = new BigNumberDateHelper(
            $this->createMock(ManagerRegistry::class),
            $this->createMock(AclHelper::class),
            $localeSettings
        );

        $period = $helper->getLastWeekPeriod();

        $lastDayNumber  = $calendar->getFirstDayOfWeek() + 5;
        $lastDayString = date('l', strtotime("Sunday +{$lastDayNumber} days"));
        $end = new \DateTime('last ' . $lastDayString, new \DateTimeZone($timezone));
        $end->setTime(0, 0, 0)->modify('1 day');
        $start = clone $end;
        $start->modify('-7 days');
        $expectedPeriod = [
            'start' => $start,
            'end'   => $end
        ];

        $this->assertEquals($expectedPeriod, $period);
        $this->assertEquals($expectedWeekStart, $period['start']->format('l'));
    }

    public function localeDataProvider(): array
    {
        return [
            'US locale with start week day is Sunday' => [
               'locale' => 'en_US',
               'timezone' => 'America/Los_Angeles',
               'expectedWeekStart' => 'Sunday'
            ],
            'France locale with start week day is Monday' => [
               'locale' => 'fr_FR',
               'timezone' => 'Europe/Paris',
               'expectedWeekStart' => 'Monday'
            ]
        ];
    }
}
