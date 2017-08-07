<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli;

use Oro\Bundle\TestFrameworkBundle\Behat\Cli\SuiteSetDividerController;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfigurationRegistry;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;

class SuiteSetDividerControllerTest extends \PHPUnit_Framework_TestCase
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
    }

    public function testExecute()
    {
        $suiteConfigRegistry = $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteConfigRegistry->expects($this->once())->method('genererateSets')->with(5);

        $controller = new SuiteSetDividerController($suiteConfigRegistry);
        $controller->execute(new InputStub('', [], ['suite-set-divider' => 5]), new OutputStub());
    }

    public function testNotExecute()
    {
        $suiteConfigRegistry = $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteConfigRegistry->expects($this->never())->method('genererateSets');

        $controller = new SuiteSetDividerController($suiteConfigRegistry);
        $controller->execute(new InputStub('', [], []), new OutputStub());
    }
}
