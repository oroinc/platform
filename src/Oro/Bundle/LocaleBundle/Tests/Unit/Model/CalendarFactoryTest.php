<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Model;

use Oro\Bundle\LocaleBundle\Model\CalendarFactory;

class CalendarFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CalendarFactory
     */
    protected $factory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $container;

    protected function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->factory = new CalendarFactory($this->container);
    }

    /**
     * @dataProvider getCalendarDataProvider
     */
    public function testGetCalendar($locale, $language)
    {
        $calendar = $this->createMock(
            'Oro\Bundle\LocaleBundle\Model\Calendar',
            array('setLocale', 'setLanguage')
        );
        $calendar->expects($this->once())->method('setLocale')->with($locale);
        $calendar->expects($this->once())->method('setLanguage')->with($language);

        $this->container->expects($this->once())->method('get')
            ->with('oro_locale.calendar')
            ->will($this->returnValue($calendar));

        $this->assertEquals($calendar, $this->factory->getCalendar($locale, $language));
    }

    public function getCalendarDataProvider()
    {
        return array(
            array('en_US', 'ru_RU'),
            array(null, null),
        );
    }
}
