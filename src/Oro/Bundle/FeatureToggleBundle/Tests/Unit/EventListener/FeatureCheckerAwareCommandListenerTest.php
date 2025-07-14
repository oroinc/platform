<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\EventListener\FeatureCheckerAwareCommandListener;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Stub\CommandStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FeatureCheckerAwareCommandListenerTest extends TestCase
{
    private FeatureChecker&MockObject $featureChecker;
    private FeatureCheckerAwareCommandListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new FeatureCheckerAwareCommandListener($this->featureChecker);
    }

    public function testWhenCommandNotRequireFeatureChecker(): void
    {
        $command = $this->createMock(Command::class);
        $event = new ConsoleCommandEvent(
            $command,
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );

        $this->listener->onConsoleCommand($event);
    }

    public function testWhenCommandRequireFeatureChecker(): void
    {
        $command = new CommandStub('stub:command');
        $event = new ConsoleCommandEvent(
            $command,
            $this->createMock(InputInterface::class),
            $this->createMock(OutputInterface::class)
        );

        $this->listener->onConsoleCommand($event);

        $this->assertSame($this->featureChecker, $command->getFeatureChecker());
    }
}
