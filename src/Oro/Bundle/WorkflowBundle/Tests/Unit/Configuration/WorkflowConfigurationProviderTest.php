<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigFinderFactory;
use Oro\Bundle\WorkflowBundle\Configuration\Import\ResourceFileImportProcessorFactory;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowFileImportProcessorFactory;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowImportProcessorSupervisorFactory;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\YamlFileCachedReader;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationImportsProcessor;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowListConfiguration;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowConfigurationImportException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Integration test
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowListConfiguration */
    private $configuration;
    private KernelInterface $kernel;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->configuration = new WorkflowListConfiguration(new WorkflowConfiguration());
    }

    private function buildProvider(array $bundles): WorkflowConfigurationProvider
    {
        $finderFactory = new ConfigFinderFactory($bundles, $this->kernel);
        $workflowFinderBuilder = new WorkflowConfigFinderBuilder($finderFactory);
        $workflowFinderBuilder->setSubDirectory('/Resources/config/oro/');
        $workflowFinderBuilder->setAppSubDirectory('/config/oro/workflows/');
        $workflowFinderBuilder->setFileName('workflows.yml');

        $fileReader = new YamlFileCachedReader();

        $fileLocator = $this->createMock(FileLocatorInterface::class);

        $importsProcessor = new WorkflowConfigurationImportsProcessor();
        $importsProcessor->addImportProcessorFactory(new ResourceFileImportProcessorFactory($fileReader, $fileLocator));
        $importsProcessor->addImportProcessorFactory(new WorkflowFileImportProcessorFactory($fileReader, $fileLocator));
        $importsProcessor->addImportProcessorFactory(
            new WorkflowImportProcessorSupervisorFactory($fileReader, $workflowFinderBuilder)
        );

        return new WorkflowConfigurationProvider(
            $this->configuration,
            $workflowFinderBuilder,
            $fileReader,
            $importsProcessor
        );
    }

    public function testGetWorkflowDefinitionsIncorrectConfiguration(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $bundles = [new Stub\IncorrectConfiguration\IncorrectConfigurationBundle()];
        $configurationProvider = $this->buildProvider($bundles);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    public function testGetWorkflowDefinitionsIncorrectSplitConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Resource "first_workflow.yml" is unreadable');

        $bundles = [new Stub\IncorrectSplitConfig\IncorrectSplitConfigBundle()];
        $configurationProvider = $this->buildProvider($bundles);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    public function testGetWorkflowDefinitionsDuplicateConfiguration(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $bundles = [
            new Stub\CorrectConfiguration\CorrectConfigurationBundle(),
            new Stub\DuplicateConfiguration\DuplicateConfigurationBundle()
        ];
        $configurationProvider = $this->buildProvider($bundles);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    public function testGetWorkflowDefinitions(): void
    {
        $bundles = [
            new Stub\CorrectConfiguration\CorrectConfigurationBundle(),
            new Stub\EmptyConfiguration\EmptyConfigurationBundle()
        ];
        $configurationProvider = $this->buildProvider($bundles);

        $this->assertEquals(
            $this->getExpectedWorkflowConfiguration('CorrectConfiguration'),
            $configurationProvider->getWorkflowDefinitionConfiguration()
        );
    }

    public function testGetSplittedWorkflowDefinitions(): void
    {
        $bundles = [
            new Stub\CorrectSplitConfiguration\CorrectSplitConfigurationBundle(),
            new Stub\EmptyConfiguration\EmptyConfigurationBundle()
        ];
        $configurationProvider = $this->buildProvider($bundles);
        $expectedConfiguration = $this->getExpectedWorkflowConfiguration('CorrectSplitConfiguration');
        $providedConfig = $configurationProvider->getWorkflowDefinitionConfiguration();

        $this->assertEquals($expectedConfiguration, $providedConfig);
    }

    public function testGetWorkflowDefinitionsFilterByDirectory(): void
    {
        $bundles = [
            new Stub\CorrectConfiguration\CorrectConfigurationBundle(),
            new Stub\EmptyConfiguration\EmptyConfigurationBundle()
        ];
        $configurationProvider = $this->buildProvider($bundles);

        $this->assertEquals(
            $this->getExpectedWorkflowConfiguration('CorrectConfiguration'),
            $configurationProvider->getWorkflowDefinitionConfiguration(
                [__DIR__ . '/Stub/CorrectConfiguration']
            )
        );

        $this->assertEmpty(
            $configurationProvider->getWorkflowDefinitionConfiguration(
                [__DIR__ . '/Stub/EmptyConfiguration']
            )
        );
    }

    public function testGetWorkflowDefinitionsFilterByWorkflow(): void
    {
        $bundles = [
            new Stub\CorrectConfiguration\CorrectConfigurationBundle(),
            new Stub\EmptyConfiguration\EmptyConfigurationBundle()
        ];
        $configurationProvider = $this->buildProvider($bundles);

        $expectedWorkflows = $this->getExpectedWorkflowConfiguration('CorrectConfiguration');
        unset($expectedWorkflows['second_workflow']);

        $this->assertEquals(
            $expectedWorkflows,
            $configurationProvider->getWorkflowDefinitionConfiguration(
                null,
                ['first_workflow']
            )
        );

        $this->assertEmpty(
            $configurationProvider->getWorkflowDefinitionConfiguration(
                null,
                ['not_existing_workflow']
            )
        );
    }

    public function testImportConfigToReuse(): void
    {
        $bundles = [
            new Stub\ImportConfiguration\ImportConfigurationBundle(),
            new Stub\ImportReuseConfiguration\ImportReuseConfigurationBundle()
        ];

        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration(
            null,
            ['workflow_with_config_reuse']
        );

        $expected = $this->getExpectedWorkflowConfiguration('ImportReuseConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportConfigWithNumericArrays(): void
    {
        $bundles = [
            new Stub\ImportConfiguration\ImportConfigurationBundle(),
            new Stub\ImportAppendNumericConfiguration\ImportAppendNumericConfigurationBundle()
        ];

        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration(
            null,
            ['workflow_with_numeric_array']
        );

        $expected = $this->getExpectedWorkflowConfiguration('ImportAppendNumericConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportSelfContainedConfig(): void
    {
        $bundles = [new Stub\ImportSelfConfiguration\ImportSelfConfigurationBundle];

        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration();

        $expected = $this->getExpectedWorkflowConfiguration('ImportSelfConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportComplexChainConfig(): void
    {
        $bundles = [
            new Stub\ImportComplexConfiguration\ImportComplexConfigurationBundle,
            new Stub\ImportSelfConfiguration\ImportSelfConfigurationBundle
        ];

        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration(
            null,
            ['chained_result']
        );

        $expected = $this->getExpectedWorkflowConfiguration('ImportComplexConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportWorkflowPartFromOuterFile(): void
    {
        $bundles = [new Stub\ImportPartsFromOuterFileConfiguration\ImportPartsFromOuterFileConfigurationBundle];

        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration(
            null,
            ['with_parts_from_file']
        );

        $expected = $this->getExpectedWorkflowConfiguration('ImportPartsFromOuterFileConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportWorkflowPartFromSplitConfigFile(): void
    {
        $bundles = [new Stub\ImportPartsFromSplitFileConfiguration\ImportPartsFromSplitFileConfigurationBundle()];
        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration();

        $expected = $this->getExpectedWorkflowConfiguration('ImportPartsFromSplitFileConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportWrongConfiguration(): void
    {
        $bundles = [new Stub\ImportIncorrectOptions\ImportIncorrectOptionsBundle];

        $configurationProvider = $this->buildProvider($bundles);

        $this->expectException(WorkflowConfigurationImportException::class);
        $message = <<<TEXT
Unknown config import directive. Given options: array (
  'workflow' => 'some_workflow_to_import',
  'as' => 'recipient_workflow',
)
TEXT;
        $this->expectExceptionMessage($message);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    public function testImportUnknownWorkflowException(): void
    {
        $bundles = [new Stub\ImportUnknownWorkflow\ImportUnknownWorkflowBundle];

        $configurationProvider = $this->buildProvider($bundles);

        $this->expectException(WorkflowConfigurationImportException::class);
        $this->expectExceptionMessage(
            'Error occurs while importing workflow for `for_that`. ' .
            'Error: "Can not find workflow `not_exists` for import." in '
        );

        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    public function testImportComplexRecursionAmongBundles(): void
    {
        $bundles = [
            new Stub\ImportRecursionFirstWorkflow\ImportRecursionFirstWorkflowBundle(),
            new Stub\ImportRecursionSecondWorkflow\ImportRecursionSecondWorkflowBundle(),
            new Stub\ImportRecursionThirdWorkflow\ImportRecursionThirdWorkflowBundle()
        ];

        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration();

        $expected = $this->getExpectedWorkflowConfiguration('ImportRecursionThirdWorkflow');

        $this->assertEquals($expected, $configuration);
    }

    private function getExpectedWorkflowConfiguration(string $bundleName): array
    {
        $fileName = __DIR__ . '/Stub/' . $bundleName . '/Resources/config/oro/workflows.php';
        $this->assertFileExists($fileName);

        return include $fileName;
    }
}
