<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Model;

use Oro\Bundle\LocaleBundle\Model\Calendar;
use Oro\Bundle\LocaleBundle\Model\CalendarFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CalendarFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var CalendarFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->factory = new CalendarFactory($this->container);
    }

    /**
     * @dataProvider getCalendarDataProvider
     */
    public function testGetCalendar(?string $locale, ?string $language)
    {
        $calendar = $this->createMock(Calendar::class);
        $calendar->expects($this->once())
            ->method('setLocale')
            ->with($locale);
        $calendar->expects($this->once())
            ->method('setLanguage')
            ->with($language);

        $this->container->expects($this->once())
            ->method('get')
            ->with('oro_locale.calendar')
            ->willReturn($calendar);

        $this->assertEquals($calendar, $this->factory->getCalendar($locale, $language));
    }

    public function getCalendarDataProvider(): array
    {
        return [
            ['en_US', 'ru_RU'],
            [null, null],
        ];
    }
}
