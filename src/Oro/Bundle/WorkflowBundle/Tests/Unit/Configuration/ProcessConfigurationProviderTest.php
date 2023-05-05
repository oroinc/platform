<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionListConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerListConfiguration;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\CorrectConfiguration\CorrectConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\DuplicateConfiguration\DuplicateConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\EmptyConfiguration\EmptyConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\IncorrectConfiguration\IncorrectConfigurationBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpKernel\Kernel;

class ProcessConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    private ProcessDefinitionListConfiguration $definitionConfiguration;
    private ProcessTriggerListConfiguration $triggerConfiguration;
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(Kernel::class);
        $this->definitionConfiguration = new ProcessDefinitionListConfiguration(new ProcessDefinitionConfiguration());
        $this->triggerConfiguration = new ProcessTriggerListConfiguration(new ProcessTriggerConfiguration());
    }

    public function testGetWorkflowDefinitionsIncorrectConfiguration()
    {
        $this->expectException(InvalidConfigurationException::class);
        $bundles = [new IncorrectConfigurationBundle()];
        $configurationProvider = new ProcessConfigurationProvider(
            $bundles,
            $this->definitionConfiguration,
            $this->triggerConfiguration,
            $this->kernel
        );
        $configurationProvider->getProcessConfiguration();
    }

    public function testGetWorkflowDefinitionsDuplicateConfiguration()
    {
        $bundles = [new CorrectConfigurationBundle(), new DuplicateConfigurationBundle()];
        $configurationProvider = new ProcessConfigurationProvider(
            $bundles,
            $this->definitionConfiguration,
            $this->triggerConfiguration,
            $this->kernel
        );

        self::assertEquals(
            $this->getExpectedProcessConfiguration('DuplicateConfiguration'),
            $configurationProvider->getProcessConfiguration()
        );
    }

    public function testGetWorkflowDefinitions()
    {
        $bundles = [new CorrectConfigurationBundle(), new EmptyConfigurationBundle()];
        $configurationProvider = new ProcessConfigurationProvider(
            $bundles,
            $this->definitionConfiguration,
            $this->triggerConfiguration,
            $this->kernel
        );

        $this->assertEquals(
            $this->getExpectedProcessConfiguration('CorrectConfiguration'),
            $configurationProvider->getProcessConfiguration()
        );
    }

    public function testGetWorkflowDefinitionsFilterByDirectory()
    {
        $bundles = [new CorrectConfigurationBundle(), new EmptyConfigurationBundle()];
        $configurationProvider = new ProcessConfigurationProvider(
            $bundles,
            $this->definitionConfiguration,
            $this->triggerConfiguration,
            $this->kernel
        );

        $this->assertEquals(
            $this->getExpectedProcessConfiguration('CorrectConfiguration'),
            $configurationProvider->getProcessConfiguration(
                [__DIR__ . '/Stub/CorrectConfiguration']
            )
        );

        $emptyConfiguration = $configurationProvider->getProcessConfiguration(
            [__DIR__ . '/Stub/EmptyConfiguration']
        );
        $this->assertEmpty($emptyConfiguration[ProcessConfigurationProvider::NODE_DEFINITIONS]);
        $this->assertEmpty($emptyConfiguration[ProcessConfigurationProvider::NODE_TRIGGERS]);
    }

    public function testGetWorkflowDefinitionsFilterByProcess()
    {
        $bundles = [new CorrectConfigurationBundle(), new EmptyConfigurationBundle()];
        $configurationProvider = new ProcessConfigurationProvider(
            $bundles,
            $this->definitionConfiguration,
            $this->triggerConfiguration,
            $this->kernel
        );

        $expectedConfiguration = $this->getExpectedProcessConfiguration('CorrectConfiguration');
        unset($expectedConfiguration[ProcessConfigurationProvider::NODE_DEFINITIONS]['another_definition']);

        $this->assertEquals(
            $expectedConfiguration,
            $configurationProvider->getProcessConfiguration(
                null,
                ['test_definition']
            )
        );

        $emptyConfiguration = $configurationProvider->getProcessConfiguration(
            null,
            ['not_existing_definition']
        );
        $this->assertEmpty($emptyConfiguration[ProcessConfigurationProvider::NODE_DEFINITIONS]);
        $this->assertEmpty($emptyConfiguration[ProcessConfigurationProvider::NODE_TRIGGERS]);
    }

    private function getExpectedProcessConfiguration(string $bundleName): array
    {
        $fileName = __DIR__ . '/Stub/' . $bundleName . '/Resources/config/oro/processes.php';
        $this->assertFileExists($fileName);

        return include $fileName;
    }
}
