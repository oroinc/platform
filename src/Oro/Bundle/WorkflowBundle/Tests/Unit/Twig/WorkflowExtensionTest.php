<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Twig;

use Oro\Bundle\WorkflowBundle\Twig\WorkflowExtension;

class WorkflowExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var WorkflowExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new WorkflowExtension(
            $this->workflowRegistry,
            $this->workflowManager,
            $this->configProvider
        );
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(6, $functions);

        $expectedFunctions = array(
            'has_workflows',
            'has_workflow_items',
            'get_workflow',
            'get_workflow_item_current_step',
            'get_primary_workflow_name',
            'get_primary_workflow_item'
        );

        /** @var \Twig_SimpleFunction $function */
        foreach ($functions as $function) {
            $this->assertInstanceOf('\Twig_SimpleFunction', $function);
            $this->assertContains($function->getName(), $expectedFunctions);
        }
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowExtension::NAME, $this->extension->getName());
    }

    /**
     * @dataProvider workflowsDataProvider
     * @param array $result
     * @param bool $expected
     */
    public function testHasWorkflows($result, $expected)
    {
        $class = '\stdClass';
        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflowsByEntityClass')
            ->with($class)
            ->will($this->returnValue($result));

        $this->assertEquals($expected, $this->extension->hasWorkflows($class));
    }

    public function workflowsDataProvider()
    {
        return array(
            array(array(), false),
            array(null, false),
            array(array('test_workflow'), true)
        );
    }

    public function testGetWorkflow()
    {
        $workflowIdentifier = 'test';
        $workflow = $this->assertWorkflow($workflowIdentifier);
        $this->assertSame($workflow, $this->extension->getWorkflow($workflowIdentifier));
    }

    public function testGetWorkflowItemCurrentStep()
    {
        $stepName = 'testStep';
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getCurrentStepName')
            ->will($this->returnValue($stepName));
        $workflow = $this->assertWorkflow($workflowItem);

        $stepManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\StepManager')
            ->disableOriginalConstructor()
            ->getMock();
        $stepManager->expects($this->once())
            ->method('getStep')
            ->with($stepName);

        $workflow->expects($this->once())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));

        $this->extension->getWorkflowItemCurrentStep($workflowItem);
    }

    public function testGetPrimaryWorkflowNameNotConfigurable()
    {
        $className = '\stdClass';
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className);
        $this->assertNull($this->extension->getPrimaryWorkflowName($className));
    }

    public function testGetPrimaryWorkflowName()
    {
        $className = '\stdClass';
        $workflowName = 'primaryWorkflow';

        $this->assertCallToGetPrimaryWorkflowName($className, $workflowName);
        $this->assertEquals($workflowName, $this->extension->getPrimaryWorkflowName($className));
    }

    public function testGetPrimaryWorkflowItemNoWorkflow()
    {
        $object = new \stdClass();
        $this->assertNull($this->extension->getPrimaryWorkflowItem($object));
    }

    public function testGetPrimaryWorkflowItemExisting()
    {
        $object = new \stdClass();
        $workflowName = 'primaryWorkflow';
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertCallToGetPrimaryWorkflowName(get_class($object), $workflowName);
        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($object, $workflowName)
            ->will($this->returnValue(array($workflowItem)));

        $this->assertSame($workflowItem, $this->extension->getPrimaryWorkflowItem($object));
    }

    public function testGetPrimaryWorkflowItemNew()
    {
        $object = new \stdClass();
        $workflowName = 'primaryWorkflow';
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertCallToGetPrimaryWorkflowName(get_class($object), $workflowName);
        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($object, $workflowName);

        $workflow = $this->assertWorkflow($workflowName);
        $workflow->expects($this->once())
            ->method('createWorkflowItem')
            ->will($this->returnValue($workflowItem));

        $this->assertSame($workflowItem, $this->extension->getPrimaryWorkflowItem($object));
    }

    /**
     * @dataProvider trueFalseDataProvider
     */
    public function testHasWorkflowItemsWithPrimary($result)
    {
        $entity = new \stdClass();
        $this->workflowManager->expects($this->once())
            ->method('checkWorkflowItemsByEntity')
            ->with($entity)
            ->will($this->returnValue($result));
        $this->assertEquals($result, $this->extension->hasWorkflowItems($entity));
    }

    public function trueFalseDataProvider()
    {
        return array(
            array(true),
            array(false)
        );
    }

    /**
     * @dataProvider trueFalseDataProvider
     */
    public function testHasWorkflowItemsWithoutPrimary($result)
    {
        $entity = new \stdClass();
        $workflowName = 'primary';
        $this->assertCallToGetPrimaryWorkflowName(get_class($entity), $workflowName);

        $this->workflowManager->expects($this->once())
            ->method('checkWorkflowItemsByEntity')
            ->with($entity, $workflowName)
            ->will($this->returnValue($result));

        $this->assertEquals($result, $this->extension->hasWorkflowItems($entity, true));
    }

    protected function assertCallToGetPrimaryWorkflowName($className, $workflowName)
    {
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('primary')
            ->will($this->returnValue($workflowName));

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->will($this->returnValue($config));
    }

    protected function assertWorkflow($workflowIdentifier)
    {
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowIdentifier)
            ->will($this->returnValue($workflow));
        return $workflow;
    }
}
