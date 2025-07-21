<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Action\Handler;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionScheduledHandler;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheScheduleArgumentsBuilderInterface;
use Oro\Bundle\CacheBundle\Action\Transformer\DateTimeToStringTransformerInterface;
use Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InvalidateCacheActionScheduledHandlerTest extends TestCase
{
    private DeferredScheduler&MockObject $deferredScheduler;
    private InvalidateCacheScheduleArgumentsBuilderInterface&MockObject $scheduleArgumentsBuilder;
    private DateTimeToStringTransformerInterface&MockObject $cronFormatTransformer;
    private InvalidateCacheActionScheduledHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->deferredScheduler = $this->createMock(DeferredScheduler::class);
        $this->scheduleArgumentsBuilder = $this->createMock(InvalidateCacheScheduleArgumentsBuilderInterface::class);
        $this->cronFormatTransformer = $this->createMock(DateTimeToStringTransformerInterface::class);

        $this->handler = new InvalidateCacheActionScheduledHandler(
            $this->deferredScheduler,
            $this->scheduleArgumentsBuilder,
            $this->cronFormatTransformer
        );
    }

    public function testHandleForRemovingSchedule(): void
    {
        $dataStorage = new InvalidateCacheDataStorage([
            InvalidateCacheActionScheduledHandler::PARAM_HANDLER_SERVICE_NAME => 'service'
        ]);
        $arguments = [
            'service=service',
            'parameters=' . serialize([]),
        ];

        $this->scheduleArgumentsBuilder->expects(self::once())
            ->method('build')
            ->with($dataStorage)
            ->willReturn($arguments);

        $this->deferredScheduler->expects(self::once())
            ->method('removeScheduleForCommand')
            ->with(InvalidateCacheScheduleCommand::getDefaultName(), $arguments);

        $this->deferredScheduler->expects(self::never())
            ->method('addSchedule');

        $this->deferredScheduler->expects(self::once())
            ->method('flush');

        $this->handler->handle($dataStorage);
    }

    public function testHandleForAddingSchedule(): void
    {
        $time = new \DateTime();
        $dataStorage = new InvalidateCacheDataStorage([
            InvalidateCacheActionScheduledHandler::PARAM_HANDLER_SERVICE_NAME => 'service',
            InvalidateCacheActionScheduledHandler::PARAM_INVALIDATE_TIME => $time,
            'test' => 'string'
        ]);
        $arguments = [
            'service=service',
            'parameters=' . serialize(['test' => 'string']),
        ];
        $cronDefinition = '1 3 5 2 *';

        $this->scheduleArgumentsBuilder->expects(self::once())
            ->method('build')
            ->with($dataStorage)
            ->willReturn($arguments);

        $this->cronFormatTransformer->expects(self::once())
            ->method('transform')
            ->with($time)
            ->willReturn($cronDefinition);

        $this->deferredScheduler->expects(self::once())
            ->method('removeScheduleForCommand')
            ->with(InvalidateCacheScheduleCommand::getDefaultName(), $arguments);

        $this->deferredScheduler->expects(self::once())
            ->method('addSchedule')
            ->with(InvalidateCacheScheduleCommand::getDefaultName(), $arguments, $cronDefinition);

        $this->deferredScheduler->expects(self::once())
            ->method('flush');

        $this->handler->handle($dataStorage);
    }
}
