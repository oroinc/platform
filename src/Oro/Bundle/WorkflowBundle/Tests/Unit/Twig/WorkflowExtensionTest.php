<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Twig;

use Oro\Bundle\WorkflowBundle\Twig\WorkflowExtension;

class WorkflowExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Oro\Bundle\WorkflowBundle\Model\WorkflowManager|\PHPUnit_Framework_MockObject_MockObject
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
        $this->assertCount(2, $functions);

        $expectedFunctions = [
            'has_workflows',
            'has_workflow_items'
        ];

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

    public function workflowDataProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    public function workflowItemDataProvider()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            [$workflowItem, true],
            [null, false],
        ];
    }

    public function stepsDataProvider()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            [$workflowItem, true],
            [null, false],
        ];
    }
}
