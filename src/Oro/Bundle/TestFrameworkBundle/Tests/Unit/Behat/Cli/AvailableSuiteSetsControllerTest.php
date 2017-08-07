<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli;

use Oro\Bundle\TestFrameworkBundle\Behat\Cli\AvailableSuiteSetsController;
use Oro\Bundle\TestFrameworkBundle\Behat\Suite\SuiteConfigurationRegistry;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;

class AvailableSuiteSetsControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testConfigure()
    {
        $suiteConfigRegistry = $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controller = new AvailableSuiteSetsController($suiteConfigRegistry);
        $command = new Command('test');

        $controller->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('available-suite-sets'));
        $this->assertFalse($command->getDefinition()->getOption('available-suite-sets')->isValueRequired());
    }

    public function testExecute()
    {
        $suiteConfigRegistry = $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteConfigRegistry->method('getSets')->willReturn(['one' => 1, 'two' => 2, 'three' => 3]);
        $output = new OutputStub();

        $controller = new AvailableSuiteSetsController($suiteConfigRegistry);
        $returnCode = $controller->execute(new InputStub('', [], ['available-suite-sets' => true]), $output);

        $this->assertSame(0, $returnCode);
        $this->assertSame(['one', 'two', 'three'], $output->messages);
    }

    public function testNotExecute()
    {
        $suiteConfigRegistry = $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controller = new AvailableSuiteSetsController($suiteConfigRegistry);
        $returnCode = $controller->execute(new InputStub(), new OutputStub());

        $this->assertNotSame(0, $returnCode);
    }
}
