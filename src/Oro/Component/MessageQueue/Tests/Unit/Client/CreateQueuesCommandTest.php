<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\CreateQueuesCommand;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Symfony\Component\Console\Tester\CommandTester;

class CreateQueuesCommandTest extends \PHPUnit\Framework\TestCase
{
    private DestinationMetaRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private DriverInterface|\PHPUnit\Framework\MockObject\MockObject $driver;

    private CreateQueuesCommand $command;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(DestinationMetaRegistry::class);
        $this->driver = $this->createMock(DriverInterface::class);

        $this->command = new CreateQueuesCommand($this->driver, $this->registry);
    }

    public function testShouldHaveCommandName(): void
    {
        self::assertEquals('oro:message-queue:create-queues', $this->command->getName());
    }

    public function testShouldCreateQueues(): void
    {
        $destinationMeta1 = new DestinationMeta('', 'queue1');
        $destinationMeta2 = new DestinationMeta('', 'queue2');

        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([$destinationMeta1, $destinationMeta2]);

        $this->driver->expects(self::exactly(2))
            ->method('createQueue')
            ->withConsecutive(['queue1'], ['queue2']);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        self::assertStringContainsString('Creating queue: queue1', $tester->getDisplay());
        self::assertStringContainsString('Creating queue: queue2', $tester->getDisplay());
    }
}
