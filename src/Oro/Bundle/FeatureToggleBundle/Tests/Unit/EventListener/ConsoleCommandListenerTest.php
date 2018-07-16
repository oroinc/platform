<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\EventListener\ConsoleCommandListener;
use Oro\Bundle\FeatureToggleBundle\Tests\Unit\Stub\CommandStub;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;

class ConsoleCommandListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConsoleCommandListener */
    private $listener;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    public function setUp()
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)->disableOriginalConstructor()->getMock();
        $this->listener = new ConsoleCommandListener($this->featureChecker);
    }

    public function testWhenCommandFeatureDisabled()
    {
        $this->featureChecker
            ->expects($this->any())
            ->method('isResourceEnabled')
            ->with('oro:search:index', 'commands')
            ->willReturn(false);

        $command = new Command('oro:search:index');
        $input = new ArrayInput([]);
        $output = new DummyOutput();
        $event = new ConsoleCommandEvent($command, $input, $output);

        $this->listener->onConsoleCommand($event);
        $this->assertEquals(false, $event->commandShouldRun(), 'Command was not disabled even if related feature was');
    }

    public function testWhenCommandRequireFeatureChecker()
    {
        $this->featureChecker
            ->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $command = new CommandStub('stub:command');
        $input = new ArrayInput([]);
        $output = new DummyOutput();
        $event = new ConsoleCommandEvent($command, $input, $output);

        $this->listener->onConsoleCommand($event);

        $this->assertEquals(
            $this->featureChecker,
            $command->getFeatureChecker(),
            'FeatureChecker was not injected properly'
        );
    }
}
