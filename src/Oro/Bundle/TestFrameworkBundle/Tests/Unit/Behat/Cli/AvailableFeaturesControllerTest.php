<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli;

use Behat\Testwork\Specification\SpecificationFinder;
use Behat\Testwork\Suite\Generator\SuiteGenerator;
use Behat\Testwork\Suite\GenericSuite;
use Behat\Testwork\Suite\SuiteRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Cli\AvailableFeaturesController;
use Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Specification\Stub\SpecificationLocatorStub;
use Oro\Component\Testing\Unit\Command\Stub\InputStub;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;

class AvailableFeaturesControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigure()
    {
        $suiteRegistry = new SuiteRegistry();
        $specificationFinder = new SpecificationFinder();
        $controller = new AvailableFeaturesController($suiteRegistry, $specificationFinder);
        $command = new Command('test');

        $controller->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('available-features'));
        $this->assertFalse($command->getDefinition()->getOption('available-features')->isValueRequired());
    }

    public function testExecute()
    {
        /** @var SuiteGenerator|\PHPUnit\Framework\MockObject\MockObject $generator */
        $generator = $this->getMockBuilder(SuiteGenerator::class)->getMock();
        $generator->method('supportsTypeAndSettings')->willReturn(true);
        $generator->method('generateSuite')->willReturn(new GenericSuite('AcmeSuite', []));

        $suiteRegistry = new SuiteRegistry();
        $suiteRegistry->registerSuiteGenerator($generator);
        $suiteRegistry->registerSuiteConfiguration('AcmeSuite', null, []);

        $specificationFinder = new SpecificationFinder();
        $specificationFinder->registerSpecificationLocator(new SpecificationLocatorStub(5));

        $output = new OutputStub();

        $controller = new AvailableFeaturesController($suiteRegistry, $specificationFinder);
        $returnCode = $controller->execute(new InputStub('', [], ['available-features' => true]), $output);

        $this->assertSame(0, $returnCode);
        $this->assertCount(5, $output->messages);
    }

    public function testNotExecute()
    {
        $suiteRegistry = new SuiteRegistry();
        $specificationFinder = new SpecificationFinder();
        $controller = new AvailableFeaturesController($suiteRegistry, $specificationFinder);
        $returnCode = $controller->execute(new InputStub(), new OutputStub());

        $this->assertNotSame(0, $returnCode);
    }
}
