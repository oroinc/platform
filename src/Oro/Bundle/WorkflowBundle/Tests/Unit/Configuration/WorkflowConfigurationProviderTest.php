<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowListConfiguration;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowConfigurationImportException;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WorkflowConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowListConfiguration
     */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new WorkflowListConfiguration(new WorkflowConfiguration());
    }

    protected function tearDown()
    {
        unset($this->configuration);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testGetWorkflowDefinitionsIncorrectConfiguration()
    {
        $bundles = [new Stub\IncorrectConfiguration\IncorrectConfigurationBundle()];
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Resource "first_workflow.yml" is unreadable
     */
    public function testGetWorkflowDefinitionsIncorrectSplitConfig()
    {
        $bundles = [new Stub\IncorrectSplitConfig\IncorrectSplitConfigBundle()];
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);
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
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    public function testGetWorkflowDefinitions()
    {
        $bundles = [
            new Stub\CorrectConfiguration\CorrectConfigurationBundle(),
            new Stub\EmptyConfiguration\EmptyConfigurationBundle()
        ];
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

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
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);
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
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

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
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

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

        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

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

        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

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

        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration(
            null,
            ['chained_result']
        );

        $expected = $this->getExpectedWokflowConfiguration('ImportComplexConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportWorkflowPartFromFile()
    {
        $bundles = [new Stub\ImportPartsFromFileConfiguration\ImportPartsFromFileConfigurationBundle];

        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

        $configuration = $configurationProvider->getWorkflowDefinitionConfiguration(
            null,
            ['with_parts_from_file']
        );

        $expected = $this->getExpectedWokflowConfiguration('ImportPartsFromFileConfiguration');

        $this->assertEquals($expected, $configuration);
    }

    public function testImportWrongConfiguration()
    {
        $bundles = [new Stub\ImportIncorrectOptions\ImportIncorrectOptionsBundle];

        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

        $this->expectException(WorkflowConfigurationImportException::class);
        $this->expectExceptionMessage(
            '`workflow` type import directive options `as` and `replace` are required.' .
            ' Given "- { workflow: some_workflow_to_import, as: recipient_workflow }'
        );
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    public function testImportRecursionError()
    {
        $bundles = [new Stub\ImportRecursionErrorConfiguration\ImportRecursionErrorConfigurationBundle];

        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageRegExp(
            '/Recursion met\. File `([^`]+)` tries to import workflow `points_to_normal` ' .
            'for `points_to_other` that imports it too in `([^`]+)`/'
        );

        $configurationProvider->getWorkflowDefinitionConfiguration(null, ['for_that']);
    }

    public function testImportUnknownWorkflowException()
    {
        $bundles = [new Stub\ImportUnknownWorkflow\ImportUnknownWorkflowBundle];

        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

        $this->expectException(WorkflowConfigurationImportException::class);
        $this->expectExceptionMessage(
            'Error occurs while importing workflow for `for_that`. ' .
            'Error: "Can not find workflow `not_exists` for import.`" in '
        );

        $configurationProvider->getWorkflowDefinitionConfiguration();
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
