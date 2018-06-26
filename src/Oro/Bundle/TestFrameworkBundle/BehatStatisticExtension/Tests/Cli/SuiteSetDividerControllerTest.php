<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Cli;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli\SuiteSetDividerController;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Stub\InputStub;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;

class SuiteSetDividerControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigure()
    {
        $suiteConfigRegistry = $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controller = new SuiteSetDividerController($suiteConfigRegistry);
        $command = new Command('test');

        $controller->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('suite-set-divider'));
        $this->assertTrue($command->getDefinition()->getOption('suite-set-divider')->isValueRequired());

        $this->assertTrue($command->getDefinition()->hasOption('max_suite_set_execution_time'));
        $this->assertTrue($command->getDefinition()->getOption('max_suite_set_execution_time')->isValueRequired());
    }

    public function testDivideByCount()
    {
        $suiteConfigRegistry = $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteConfigRegistry->expects($this->once())->method('generateSetsDividedByCount')->with(5);

        $controller = new SuiteSetDividerController($suiteConfigRegistry);
        $controller->execute(new InputStub('', [], ['suite-set-divider' => 5]), new OutputStub());
    }

    public function testDivideByTimeExecution()
    {
        $suiteConfigRegistry = $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteConfigRegistry->expects($this->once())->method('generateSetsByMaxExecutionTime')->with(500);

        $controller = new SuiteSetDividerController($suiteConfigRegistry);
        $controller->execute(new InputStub('', [], ['max_suite_set_execution_time' => 500]), new OutputStub());
    }

    public function testNotExecute()
    {
        $suiteConfigRegistry = $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteConfigRegistry->expects($this->never())->method('generateSetsDividedByCount');
        $suiteConfigRegistry->expects($this->never())->method('generateSetsByMaxExecutionTime');

        $controller = new SuiteSetDividerController($suiteConfigRegistry);
        $controller->execute(new InputStub('', [], []), new OutputStub());
    }
}
