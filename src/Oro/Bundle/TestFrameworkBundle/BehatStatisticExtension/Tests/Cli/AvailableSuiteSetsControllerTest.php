<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Cli;

use Doctrine\DBAL\Connection;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider\FeatureAvgTimeRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli\AvailableSuiteSetsController;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\FeaturePathLocator;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Stub\InputStub;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;

class AvailableSuiteSetsControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigure()
    {
        $controller = new AvailableSuiteSetsController(
            $this->getSuiteConfigRegistryMock(),
            new FeatureAvgTimeRegistry(),
            $this->getFeaturePathLocatorMock()
        );
        $command = new Command('test');

        $controller->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('available-suite-sets'));
        $this->assertFalse($command->getDefinition()->getOption('available-suite-sets')->isValueRequired());
    }

    public function testExecute()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|SuiteConfigurationRegistry $suiteConfigRegistry */
        $suiteConfigRegistry = $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $suiteConfigRegistry->method('getSets')->willReturn(['one' => 1, 'two' => 2, 'three' => 3]);
        $output = new OutputStub();

        $controller = new AvailableSuiteSetsController(
            $suiteConfigRegistry,
            new FeatureAvgTimeRegistry,
            $this->getFeaturePathLocatorMock()
        );
        $returnCode = $controller->execute(new InputStub('', [], ['available-suite-sets' => true]), $output);

        $this->assertSame(0, $returnCode);
        $this->assertSame(['one', 'two', 'three'], $output->messages);
    }

    public function testNotExecute()
    {
        $controller = new AvailableSuiteSetsController(
            $this->getSuiteConfigRegistryMock(),
            new FeatureAvgTimeRegistry,
            $this->getFeaturePathLocatorMock()
        );
        $returnCode = $controller->execute(new InputStub(), new OutputStub());

        $this->assertNotSame(0, $returnCode);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SuiteConfigurationRegistry
     */
    private function getSuiteConfigRegistryMock()
    {
        return $this->getMockBuilder(SuiteConfigurationRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FeaturePathLocator
     */
    private function getFeaturePathLocatorMock()
    {
        $featurePathLocator = $this->getMockBuilder(FeaturePathLocator::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRelativePath'])
            ->getMock()
        ;
        $featurePathLocator->method('getRelativePath')->willReturnArgument(0);

        return $featurePathLocator;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Connection
     */
    private function getConnectionMock()
    {
        return $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
    }
}
