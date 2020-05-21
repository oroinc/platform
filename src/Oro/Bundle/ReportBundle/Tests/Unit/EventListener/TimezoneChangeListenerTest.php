<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;
use Oro\Bundle\ReportBundle\EventListener\TimezoneChangeListener;

class TimezoneChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarDateManager|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarDateManager;

    /** @var TimezoneChangeListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->calendarDateManager = $this->createMock(CalendarDateManager::class);

        $this->listener = new TimezoneChangeListener($this->calendarDateManager);
    }

    public function testOnConfigUpdateTimezoneNotChanged()
    {
        $event = new ConfigUpdateEvent(['not.a.timezone' => ['old' => 1, 'new' => 2]]);

        $this->calendarDateManager->expects($this->never())
            ->method('handleCalendarDates');

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateTimezoneChanged()
    {
        $event = new ConfigUpdateEvent(['oro_locale.timezone' => ['old' => 1, 'new' => 2]]);

        $this->calendarDateManager->expects($this->once())
            ->method('handleCalendarDates')
            ->with(true);

        $this->listener->onConfigUpdate($event);
    }
}
