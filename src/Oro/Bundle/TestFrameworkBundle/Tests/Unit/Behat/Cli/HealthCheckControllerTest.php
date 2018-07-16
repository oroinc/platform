<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli;

use Behat\Testwork\EventDispatcher\TestworkEventDispatcher;
use Behat\Testwork\Tester\Result\ResultInterpreter;
use Oro\Bundle\TestFrameworkBundle\Behat\Cli\HealthCheckController;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\HealthCheckerInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\ResultInterpretation;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\ResultPrinterSubscriber;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli\Stub\HealthCheckerStub;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class HealthCheckControllerTest extends \PHPUnit\Framework\TestCase
{
    private $checkers = ['cs', 'fixtures'];

    public function testConfigure()
    {
        $eventDispatcher = new TestworkEventDispatcher();
        $controller = new HealthCheckController(
            $eventDispatcher,
            new ResultInterpreter(),
            new ResultPrinterSubscriber(new DummyOutput()),
            new ResultInterpretation()
        );
        $command = new Command('test');

        $controller->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('check'));
        $this->assertTrue($command->getDefinition()->getOption('check')->isValueOptional());
    }

    /**
     * @dataProvider filterCheckersProvider
     * @param $parameter
     * @param array $expectedCheckers
     */
    public function testFilterCheckers($parameter, array $expectedCheckers)
    {
        $eventDispatcher = new TestworkEventDispatcher();

        $controller = new HealthCheckController(
            $eventDispatcher,
            new ResultInterpreter(),
            new ResultPrinterSubscriber(new DummyOutput()),
            new ResultInterpretation()
        );
        foreach ($this->checkers as $checker) {
            $controller->addHealthChecker(new HealthCheckerStub($checker));
        }
        $command = new Command('test');
        $controller->configure($command);

        $output = new OutputStub();
        $input = new InputStub('', [], ['check' => $parameter]);

        $controller->execute($input, $output);

        $existCheckers = [];
        foreach ($eventDispatcher->getListeners() as $eventName => $callBacks) {
            foreach ($callBacks as $callBack) {
                /** @var EventSubscriberInterface $listener */
                $listener = $callBack[0];
                if ($listener instanceof HealthCheckerInterface) {
                    $existCheckers[] = $listener->getName();
                }
            }
        }

        sort($existCheckers);
        sort($expectedCheckers);

        $this->assertEquals($expectedCheckers, $existCheckers);
    }

    public function filterCheckersProvider()
    {
        return [
            [false, []],
            [null, ['cs', 'fixtures']],
            ['cs', ['cs']],
            ['fixtures', ['fixtures']],
        ];
    }
}
