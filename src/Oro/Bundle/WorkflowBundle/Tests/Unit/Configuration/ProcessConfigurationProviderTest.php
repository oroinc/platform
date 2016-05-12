<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\CorrectConfiguration\CorrectConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\EmptyConfiguration\EmptyConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\IncorrectConfiguration\IncorrectConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\DuplicateConfiguration\DuplicateConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessDefinitionListConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessTriggerListConfiguration;

class ProcessConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessDefinitionListConfiguration
     */
    protected $definitionConfiguration;

    /**
     * @var ProcessTriggerListConfiguration
     */
    protected $triggerConfiguration;

    protected function setUp()
    {
        $this->definitionConfiguration = new ProcessDefinitionListConfiguration(new ProcessDefinitionConfiguration());
        $this->triggerConfiguration = new ProcessTriggerListConfiguration(new ProcessTriggerConfiguration());
    }

    protected function tearDown()
    {
        unset($this->definitionConfiguration, $this->triggerConfiguration);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testGetWorkflowDefinitionsIncorrectConfiguration()
    {
        $bundles = array(new IncorrectConfigurationBundle());
        $configurationProvider = new ProcessConfigurationProvider(
            $bundles,
            $this->definitionConfiguration,
            $this->triggerConfiguration
        );
        $configurationProvider->getProcessConfiguration();
    }

    public function testGetWorkflowDefinitionsDuplicateConfiguration()
    {
        $bundles = [new CorrectConfigurationBundle(), new DuplicateConfigurationBundle()];
        $configurationProvider = new ProcessConfigurationProvider(
            $bundles,
            $this->definitionConfiguration,
            $this->triggerConfiguration
        );

        static::assertEquals(
            $this->getExpectedProcessConfiguration('DuplicateConfiguration'),
            $configurationProvider->getProcessConfiguration()
        );
    }

    public function testGetWorkflowDefinitions()
    {
        $bundles = array(new CorrectConfigurationBundle(), new EmptyConfigurationBundle());
        $configurationProvider = new ProcessConfigurationProvider(
            $bundles,
            $this->definitionConfiguration,
            $this->triggerConfiguration
        );

        $this->assertEquals(
            $this->getExpectedProcessConfiguration('CorrectConfiguration'),
            $configurationProvider->getProcessConfiguration()
        );
    }

    public function testGetWorkflowDefinitionsFilterByDirectory()
    {
        $bundles = array(new CorrectConfigurationBundle(), new EmptyConfigurationBundle());
        $configurationProvider = new ProcessConfigurationProvider(
            $bundles,
            $this->definitionConfiguration,
            $this->triggerConfiguration
        );

        $this->assertEquals(
            $this->getExpectedProcessConfiguration('CorrectConfiguration'),
            $configurationProvider->getProcessConfiguration(
                array(__DIR__ . '/Stub/CorrectConfiguration')
            )
        );

        $emptyConfiguration = $configurationProvider->getProcessConfiguration(
            array(__DIR__ . '/Stub/EmptyConfiguration')
        );
        $this->assertEmpty($emptyConfiguration[ProcessConfigurationProvider::NODE_DEFINITIONS]);
        $this->assertEmpty($emptyConfiguration[ProcessConfigurationProvider::NODE_TRIGGERS]);
    }

    public function testGetWorkflowDefinitionsFilterByProcess()
    {
        $bundles = array(new CorrectConfigurationBundle(), new EmptyConfigurationBundle());
        $configurationProvider = new ProcessConfigurationProvider(
            $bundles,
            $this->definitionConfiguration,
            $this->triggerConfiguration
        );

        $expectedConfiguration = $this->getExpectedProcessConfiguration('CorrectConfiguration');
        unset($expectedConfiguration[ProcessConfigurationProvider::NODE_DEFINITIONS]['another_definition']);

        $this->assertEquals(
            $expectedConfiguration,
            $configurationProvider->getProcessConfiguration(
                null,
                array('test_definition')
            )
        );

        $emptyConfiguration = $configurationProvider->getProcessConfiguration(
            null,
            array('not_existing_definition')
        );
        $this->assertEmpty($emptyConfiguration[ProcessConfigurationProvider::NODE_DEFINITIONS]);
        $this->assertEmpty($emptyConfiguration[ProcessConfigurationProvider::NODE_TRIGGERS]);
    }

    /**
     * @param string $bundleName
     * @return array
     */
    protected function getExpectedProcessConfiguration($bundleName)
    {
        $fileName = __DIR__ . '/Stub/' . $bundleName . '/Resources/config/process.php';
        $this->assertFileExists($fileName);
        return include $fileName;
    }
}
