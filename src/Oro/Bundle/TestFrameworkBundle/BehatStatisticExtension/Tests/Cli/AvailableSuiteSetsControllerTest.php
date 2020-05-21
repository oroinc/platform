<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Cli;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider\FeatureAvgTimeRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Cli\AvailableSuiteSetsController;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatisticManager;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\FeaturePathLocator;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Suite\SuiteConfigurationRegistry;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Stub\InputStub;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Stub\OutputStub;
use Symfony\Component\Console\Command\Command;

class AvailableSuiteSetsControllerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SuiteConfigurationRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $suiteConfigRegistry;

    /** @var FeatureAvgTimeRegistry */
    private $featureAvgTimeRegistry;

    /** @var FeaturePathLocator|\PHPUnit\Framework\MockObject\MockObject */
    private $featurePathLocator;

    /** @var FeatureStatisticManager|\PHPUnit\Framework\MockObject\MockObject */
    private $featureStatisticManager;

    /** @var AvailableSuiteSetsController */
    private $controller;

    protected function setUp(): void
    {
        $this->suiteConfigRegistry = $this->createMock(SuiteConfigurationRegistry::class);
        $this->featureAvgTimeRegistry = new FeatureAvgTimeRegistry();

        $this->featurePathLocator = $this->getMockBuilder(FeaturePathLocator::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRelativePath'])
            ->getMock();
        $this->featurePathLocator->expects($this->any())
            ->method('getRelativePath')
            ->willReturnArgument(0);

        $this->featureStatisticManager = $this->createMock(FeatureStatisticManager::class);

        $this->controller = new AvailableSuiteSetsController(
            $this->suiteConfigRegistry,
            $this->featureAvgTimeRegistry,
            $this->featurePathLocator,
            $this->featureStatisticManager
        );
    }

    public function testConfigure()
    {
        $command = new Command('test');

        $this->controller->configure($command);

        $this->assertTrue($command->getDefinition()->hasOption('available-suite-sets'));
        $this->assertFalse($command->getDefinition()->getOption('available-suite-sets')->isValueRequired());
    }

    public function testExecute()
    {
        $this->suiteConfigRegistry->expects($this->once())
            ->method('getSets')
            ->willReturn(['one' => 1, 'two' => 2, 'three' => 3]);

        $this->featureStatisticManager->expects($this->once())
            ->method('cleanOldStatistics');

        $output = new OutputStub();
        $returnCode = $this->controller->execute(new InputStub('', [], ['available-suite-sets' => true]), $output);

        $this->assertSame(0, $returnCode);
        $this->assertSame(['one', 'two', 'three'], $output->messages);
    }

    public function testNotExecute()
    {
        $returnCode = $this->controller->execute(new InputStub(), new OutputStub());

        $this->assertNotSame(0, $returnCode);
    }
}
