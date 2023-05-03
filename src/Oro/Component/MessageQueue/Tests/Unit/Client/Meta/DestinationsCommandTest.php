<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Client\Meta\DestinationsCommand;
use Symfony\Component\Console\Tester\CommandTester;

class DestinationsCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var DestinationMetaRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var DestinationsCommand */
    private $command;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(DestinationMetaRegistry::class);

        $this->command = new DestinationsCommand($this->registry);
    }

    public function testShouldHaveCommandName(): void
    {
        self::assertEquals('oro:message-queue:destinations', $this->command->getName());
    }

    public function testShouldShowMessageFoundZeroDestinationsIfAnythingInRegistry(): void
    {
        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([]);

        $output = $this->executeCommand();

        self::assertStringContainsString('Found 0 destinations', $output);
    }

    public function testShouldShowMessageFoundTwoDestinations(): void
    {
        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([
                new DestinationMeta('aClientName', 'aDestinationName'),
                new DestinationMeta('anotherClientName', 'anotherDestinationName'),
            ]);

        $output = $this->executeCommand();

        self::assertStringContainsString('Found 2 destinations', $output);
    }

    public function testShouldShowInfoAboutDestinations(): void
    {
        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([
                new DestinationMeta('aFooClientName', 'aFooDestinationName', ['fooSubscriber']),
                new DestinationMeta('aBarClientName', 'aBarDestinationName', ['barSubscriber']),
            ]);

        $output = $this->executeCommand();

        self::assertStringContainsString('aFooClientName', $output);
        self::assertStringContainsString('aFooDestinationName', $output);
        self::assertStringContainsString('fooSubscriber', $output);
        self::assertStringContainsString('aBarClientName', $output);
        self::assertStringContainsString('aBarDestinationName', $output);
        self::assertStringContainsString('barSubscriber', $output);
    }

    private function executeCommand(array $arguments = []): string
    {
        $tester = new CommandTester($this->command);
        $tester->execute($arguments);

        return $tester->getDisplay();
    }
}
