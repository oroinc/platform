<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionHandleBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionHandleBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildFromRawConfiguration()
    {
        $rawConfiguration = array('name' => 'test_workflow');
        $handledConfiguration = array('name' => 'test_workflow', 'label' => 'Test Workflow');
        $processedConfiguration = array('name' => 'test_workflow', 'label' => 'Test Workflow', 'system' => false);

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName($processedConfiguration['name']);

        $handler = $this->getMock('Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface');
        $handler->expects($this->once())->method('handle')->with($rawConfiguration)
            ->will($this->returnValue($handledConfiguration));

        $configuration = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $configuration->expects($this->once())->method('processConfiguration')->with($handledConfiguration)
            ->will($this->returnValue($processedConfiguration));

        $configurationBuilder
            = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder')
                ->disableOriginalConstructor()
                ->getMock();
        $configurationBuilder->expects($this->once())->method('buildOneFromConfiguration')
            ->with($processedConfiguration['name'], $processedConfiguration)
            ->will($this->returnValue($workflowDefinition));

        $handleBuilder = new WorkflowDefinitionHandleBuilder($handler, $configuration, $configurationBuilder);
        $this->assertEquals($workflowDefinition, $handleBuilder->buildFromRawConfiguration($rawConfiguration));
    }
}
