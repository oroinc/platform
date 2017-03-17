<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Action\Handler;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionScheduledHandler;
use Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;

class InvalidateCacheActionScheduledHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DeferredScheduler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deferredScheduler;

    /**
     * @var InvalidateCacheActionScheduledHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->deferredScheduler = $this->createMock(DeferredScheduler::class);

        $this->handler = new InvalidateCacheActionScheduledHandler($this->deferredScheduler);
    }

    public function testHandleForRemovingSchedule()
    {
        $dataStorage = new InvalidateCacheDataStorage([
            InvalidateCacheActionScheduledHandler::PARAM_HANDLER_SERVICE_NAME => 'service'
        ]);
        $arguments = [
            'service=service',
            'parameters=' . serialize([]),
        ];

        $this->deferredScheduler->expects(static::once())
            ->method('removeScheduleForCommand')
            ->with(InvalidateCacheScheduleCommand::NAME, $arguments);

        $this->deferredScheduler->expects(static::never())
            ->method('addSchedule');

        $this->deferredScheduler->expects(static::once())
            ->method('flush');

        $this->handler->handle($dataStorage);
    }

    public function testHandleForAddingSchedule()
    {
        $time = new \DateTime();
        $time->setTimestamp(100);

        $dataStorage = new InvalidateCacheDataStorage([
            InvalidateCacheActionScheduledHandler::PARAM_HANDLER_SERVICE_NAME => 'service',
            InvalidateCacheActionScheduledHandler::PARAM_INVALIDATE_TIME => $time,
            'test' => 'string'
        ]);
        $arguments = [
            'service=service',
            'parameters=' . serialize(['test' => 'string']),
        ];

        $this->deferredScheduler->expects(static::once())
            ->method('removeScheduleForCommand')
            ->with(InvalidateCacheScheduleCommand::NAME, $arguments);

        $this->deferredScheduler->expects(static::once())
            ->method('addSchedule')
            ->with(InvalidateCacheScheduleCommand::NAME, $arguments, '1 3 1 1 *');

        $this->deferredScheduler->expects(static::once())
            ->method('flush');

        $this->handler->handle($dataStorage);
    }
}
