<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Twig;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Twig\WorkflowExtension;

class WorkflowExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowManager;

    /**
     * @var WorkflowExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new WorkflowExtension($this->workflowManager);
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(5, $functions);

        $expectedFunctions = array(
            'has_workflow',
            'has_workflow_start_step',
            'has_workflow_item',
            'is_workflow_reset_allowed',
            'has_workflows',
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
     * @dataProvider workflowDataProvider
     * @param bool $result
     */
    public function testHasWorkflow($result)
    {
        $entityClass = '\stdClass';
        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflowByEntityClass')
            ->with($entityClass)
            ->will($this->returnValue($result));

        $this->assertEquals($result, $this->extension->hasWorkflow($entityClass));
    }

    public function testHasWorkflowNoEntity()
    {
        $this->workflowManager->expects($this->never())
            ->method('getApplicableWorkflow');

        $this->assertFalse($this->extension->hasWorkflow(null));
    }

    public function workflowDataProvider()
    {
        return array(
            array(true),
            array(false),
        );
    }

    /**
     * @dataProvider workflowItemDataProvider
     * @param WorkflowItem|null $result
     * @param bool $expected
     */
    public function testHasWorkflowItem($result, $expected)
    {
        $entity = new \stdClass();
        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemByEntity')
            ->with($entity)
            ->will($this->returnValue($result));

        $this->assertEquals($expected, $this->extension->hasWorkflowItem($entity));
    }

    public function workflowItemDataProvider()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        return array(
            array($workflowItem, true),
            array(null, false),
        );
    }

    public function testHasWorkflowStartStepNoWorkflow()
    {
        $entity = new \stdClass();
        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflow')
            ->with($entity);
        $this->assertFalse($this->extension->hasWorkflowStartStep($entity));
    }

    /**
     * @dataProvider stepsDataProvider
     * @param WorkflowStep|null $step
     * @param bool $expected
     */
    public function testHasWorkflowStartStep($step, $expected)
    {
        $entity = new \stdClass();
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('getStartStep')
            ->will($this->returnValue($step));
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($definition));
        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflow')
            ->with($entity)
            ->will($this->returnValue($workflow));

        $this->assertEquals($expected, $this->extension->hasWorkflowStartStep($entity));
    }

    public function stepsDataProvider()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep')
            ->disableOriginalConstructor()
            ->getMock();
        return array(
            array($workflowItem, true),
            array(null, false),
        );
    }

    /**
     * @dataProvider workflowDataProvider
     * @param bool $expected
     */
    public function testIsWorkflowActive($expected)
    {
        $this->markTestSkipped('TODO: must be fixed in scope https://magecore.atlassian.net/browse/BAP-10814');

        $entity = new \stdClass();

        $this->workflowManager->expects($this->once())
            ->method('isResetAllowed')
            ->with($entity)
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $this->extension->isResetAllowed($entity));
    }
}
