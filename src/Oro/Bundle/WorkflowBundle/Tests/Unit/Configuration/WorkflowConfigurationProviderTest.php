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
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub;

/**
 * Integration test
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WorkflowListConfiguration
     */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new WorkflowListConfiguration(new WorkflowConfiguration());
    }

    /**
     * @param array $bundles
     * @return WorkflowConfigurationProvider
     */
    protected function buildProvider(array $bundles): WorkflowConfigurationProvider
    {
        $finderFactory = new ConfigFinderFactory($bundles);
        $workflowFinderBuilder = new WorkflowConfigFinderBuilder($finderFactory);
        $workflowFinderBuilder->setSubDirectory('/Resources/config/oro/');
        $workflowFinderBuilder->setFileName('workflows.yml');

        $fileReader = new YamlFileCachedReader();

        $importsProcessor = new WorkflowConfigurationImportsProcessor();
        $importsProcessor->addImportProcessorFactory(new ResourceFileImportProcessorFactory($fileReader, []));
        $importsProcessor->addImportProcessorFactory(new WorkflowFileImportProcessorFactory($fileReader));
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

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testGetWorkflowDefinitionsIncorrectConfiguration()
    {
        $bundles = [new Stub\IncorrectConfiguration\IncorrectConfigurationBundle()];
        $configurationProvider = $this->buildProvider($bundles);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Resource "first_workflow.yml" is unreadable
     */
    public function testGetWorkflowDefinitionsIncorrectSplitConfig()
    {
        $bundles = [new Stub\IncorrectSplitConfig\IncorrectSplitConfigBundle()];
        $configurationProvider = $this->buildProvider($bundles);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testGetWorkflowDefinitionsDuplicateConfiguration()
    {
        $bundles = [
            new Stub\CorrectConfiguration\CorrectConfigurationBundle(),
            new Stub\DuplicateConfiguration\DuplicateConfigurationBundle()
        ];
        $configurationProvider = $this->buildProvider($bundles);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    public function testGetWorkflowDefinitions()
    {
        $bundles = [
            new Stub\CorrectConfiguration\CorrectConfigurationBundle(),
            new Stub\EmptyConfiguration\EmptyConfigurationBundle()
        ];
        $configurationProvider = $this->buildProvider($bundles);

        $this->assertEquals(
            $this->getExpectedWokflowConfiguration('CorrectConfiguration'),
            $configurationProvider->getWorkflowDefinitionConfiguration()
        );
    }

    public function testGetSplittedWorkflowDefinitions()
    {
        $bundles = [
            new Stub\CorrectSplitConfiguration\CorrectSplitConfigurationBundle(),
            new Stub\EmptyConfiguration\EmptyConfigurationBundle()
        ];
        $configurationProvider = $this->buildProvider($bundles);
        $expectedConfiguration = $this->getExpectedWokflowConfiguration('CorrectConfiguration');
        $providedConfig = $configurationProvider->getWorkflowDefinitionConfiguration();

        $this->assertEquals($expectedConfiguration, $providedConfig);
    }

    public function testGetWorkflowDefinitionsFilterByDirectory()
    {
        $bundles = [
            new Stub\CorrectConfiguration\CorrectConfigurationBundle(),
            new Stub\EmptyConfiguration\EmptyConfigurationBundle()
        ];
        $configurationProvider = $this->buildProvider($bundles);

        $this->assertEquals(
            $this->getExpectedWokflowConfiguration('CorrectConfiguration'),
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

    public function testGetWorkflowDefinitionsFilterByWorkflow()
    {
        $bundles = [
            new Stub\CorrectConfiguration\CorrectConfigurationBundle(),
            new Stub\EmptyConfiguration\EmptyConfigurationBundle()
        ];
        $configurationProvider = $this->buildProvider($bundles);

        $expectedWorkflows = $this->getExpectedWokflowConfiguration('CorrectConfiguration');
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

    public function testImportConfigToReuse()
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

        $expected = $this->getExpectedWokflowConfiguration('ImportReuseConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportSelfContainedConfig()
    {
        $bundles = [new Stub\ImportSelfConfiguration\ImportSelfConfigurationBundle];

        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration();

        $expected = $this->getExpectedWokflowConfiguration('ImportSelfConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportComplexChainConfig()
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

        $expected = $this->getExpectedWokflowConfiguration('ImportComplexConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportWorkflowPartFromOuterFile()
    {
        $bundles = [new Stub\ImportPartsFromOuterFileConfiguration\ImportPartsFromOuterFileConfigurationBundle];

        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration(
            null,
            ['with_parts_from_file']
        );

        $expected = $this->getExpectedWokflowConfiguration('ImportPartsFromOuterFileConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportWorkflowPartFromSplitConfigFile()
    {
        $bundles = [new Stub\ImportPartsFromSplitFileConfiguration\ImportPartsFromSplitFileConfigurationBundle()];
        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration();

        $expected = $this->getExpectedWokflowConfiguration('ImportPartsFromSplitFileConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportWrongConfiguration()
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

    public function testImportUnknownWorkflowException()
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

    public function testImportComplexRecursionAmongBundles()
    {
        $bundles = [
            new Stub\ImportRecursionFirstWorkflow\ImportRecursionFirstWorkflowBundle(),
            new Stub\ImportRecursionSecondWorkflow\ImportRecursionSecondWorkflowBundle(),
            new Stub\ImportRecursionThirdWorkflow\ImportRecursionThirdWorkflowBundle()
        ];

        $configurationProvider = $this->buildProvider($bundles);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration();

        $expected = $this->getExpectedWokflowConfiguration('ImportRecursionThirdWorkflow');

        $this->assertEquals($expected, $configuration);
    }

    /**
     * @param string $bundleName
     * @return array
     */
    protected function getExpectedWokflowConfiguration($bundleName)
    {
        $fileName = __DIR__ . '/Stub/' . $bundleName . '/Resources/config/oro/workflows.php';
        $this->assertFileExists($fileName);

        return include $fileName;
    }
}
