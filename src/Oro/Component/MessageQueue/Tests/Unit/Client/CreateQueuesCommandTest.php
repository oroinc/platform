<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\CreateQueuesCommand;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateQueuesCommandTest extends TestCase
{
    private DestinationMetaRegistry&MockObject $registry;
    private DriverInterface&MockObject $driver;
    private CreateQueuesCommand $command;

    #[\Override]
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
