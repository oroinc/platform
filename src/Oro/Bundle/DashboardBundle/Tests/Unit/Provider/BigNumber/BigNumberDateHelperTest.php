<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\BigNumber;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DashboardBundle\Provider\BigNumber\BigNumberDateHelper;
use Oro\Bundle\LocaleBundle\Model\Calendar;
use Oro\Bundle\LocaleBundle\Model\CalendarFactory;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\RegistryInterface;

class BigNumberDateHelperTest extends TestCase
{
    /**
     * @dataProvider localeDataProvider
     */
    public function testGetLastWeekPeriodForLocale(string $locale, string $timezone, string $expectedWeekStart)
    {
        $calendar = new Calendar($locale);
        $calendarFactory = $this->createMock(CalendarFactory::class);
        $calendarFactory->expects($this->any())
            ->method('getCalendar')
            ->willReturn($calendar);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_locale.timezone', false, false, null, $timezone],
                    ['oro_locale.locale', false, false, null, $locale],
                ]
            );

        $localeSettings = new LocaleSettings($configManager, $calendarFactory);
        $helper = new BigNumberDateHelper(
            $this->createMock(RegistryInterface::class),
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

    public function localeDataProvider()
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
