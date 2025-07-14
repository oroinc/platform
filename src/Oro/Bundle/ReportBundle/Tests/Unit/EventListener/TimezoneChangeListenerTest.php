<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;
use Oro\Bundle\ReportBundle\EventListener\TimezoneChangeListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TimezoneChangeListenerTest extends TestCase
{
    private CalendarDateManager&MockObject $calendarDateManager;
    private TimezoneChangeListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->calendarDateManager = $this->createMock(CalendarDateManager::class);

        $this->listener = new TimezoneChangeListener($this->calendarDateManager);
    }

    public function testOnConfigUpdateTimezoneNotChanged(): void
    {
        $event = new ConfigUpdateEvent(['not.a.timezone' => ['old' => 1, 'new' => 2]], 'global', 0);

        $this->calendarDateManager->expects($this->never())
            ->method('handleCalendarDates');

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateTimezoneChanged(): void
    {
        $event = new ConfigUpdateEvent(['oro_locale.timezone' => ['old' => 1, 'new' => 2]], 'global', 0);

        $this->calendarDateManager->expects($this->once())
            ->method('handleCalendarDates')
            ->with(true);

        $this->listener->onConfigUpdate($event);
    }
}
