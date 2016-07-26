<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\CorrectConfiguration\CorrectConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\CorrectSplitConfiguration\CorrectSplitConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\IncorrectSplitConfig\IncorrectSplitConfigBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\EmptyConfiguration\EmptyConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\IncorrectConfiguration\IncorrectConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Stub\DuplicateConfiguration\DuplicateConfigurationBundle;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowListConfiguration;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

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
        $bundles = array(new IncorrectConfigurationBundle());
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Resource "first_workflow.yml" is unreadable
     */
    public function testGetWorkflowDefinitionsIncorrectSplitConfig()
    {
        $bundles = array(new IncorrectSplitConfigBundle());
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testGetWorkflowDefinitionsDuplicateConfiguration()
    {
        $bundles = array(new CorrectConfigurationBundle(), new DuplicateConfigurationBundle());
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);
        $configurationProvider->getWorkflowDefinitionConfiguration();
    }

    public function testGetWorkflowDefinitions()
    {
        $bundles = array(new CorrectConfigurationBundle(), new EmptyConfigurationBundle());
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

        $this->assertEquals(
            $this->getExpectedWokflowConfiguration('CorrectConfiguration'),
            $configurationProvider->getWorkflowDefinitionConfiguration()
        );
    }

    public function testGetSplittedWorkflowDefinitions()
    {
        $bundles = array(new CorrectSplitConfigurationBundle(), new EmptyConfigurationBundle());
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

        $this->assertEquals(
            $this->getExpectedWokflowConfiguration('CorrectConfiguration'),
            $configurationProvider->getWorkflowDefinitionConfiguration()
        );
    }

    public function testGetWorkflowDefinitionsFilterByDirectory()
    {
        $bundles = array(new CorrectConfigurationBundle(), new EmptyConfigurationBundle());
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

        $this->assertEquals(
            $this->getExpectedWokflowConfiguration('CorrectConfiguration'),
            $configurationProvider->getWorkflowDefinitionConfiguration(
                array(__DIR__ . '/Stub/CorrectConfiguration')
            )
        );

        $this->assertEmpty(
            $configurationProvider->getWorkflowDefinitionConfiguration(
                array(__DIR__ . '/Stub/EmptyConfiguration')
            )
        );
    }

    public function testGetWorkflowDefinitionsFilterByWorkflow()
    {
        $bundles = array(new CorrectConfigurationBundle(), new EmptyConfigurationBundle());
        $configurationProvider = new WorkflowConfigurationProvider($bundles, $this->configuration);

        $expectedWorkflows = $this->getExpectedWokflowConfiguration('CorrectConfiguration');
        unset($expectedWorkflows['second_workflow']);

        $this->assertEquals(
            $expectedWorkflows,
            $configurationProvider->getWorkflowDefinitionConfiguration(
                null,
                array('first_workflow')
            )
        );

        $this->assertEmpty(
            $configurationProvider->getWorkflowDefinitionConfiguration(
                null,
                array('not_existing_workflow')
            )
        );
    }

    /**
     * @param string $bundleName
     * @return array
     */
    protected function getExpectedWokflowConfiguration($bundleName)
    {
        $fileName = __DIR__ . '/Stub/' . $bundleName . '/Resources/config/workflow.php';
        $this->assertFileExists($fileName);
        return include $fileName;
    }
}
