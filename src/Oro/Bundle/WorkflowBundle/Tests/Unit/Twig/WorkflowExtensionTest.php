<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Twig;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\StubEntity;
use Oro\Bundle\WorkflowBundle\Twig\WorkflowExtension;

use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class WorkflowExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var WorkflowExtension */
    protected $extension;

    protected function setUp()
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $workflowManagerRegistry->expects($this->any())->method('getManager')->willReturn($this->workflowManager);

        $container = self::getContainerBuilder()
            ->add('oro_workflow.registry.workflow_manager', $workflowManagerRegistry)
            ->getContainer($this);

        $this->extension = new WorkflowExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(WorkflowExtension::NAME, $this->extension->getName());
    }

    public function testHasApplicableWorkflows()
    {
        $entity = new StubEntity();
        $this->workflowManager->expects($this->once())->method('hasApplicableWorkflows')->with($entity);
        $this->callTwigFunction($this->extension, 'has_workflows', [$entity]);
    }

    public function testHasWorkflowItemsByEntity()
    {
        $entity = new StubEntity();
        $this->workflowManager->expects($this->once())->method('hasWorkflowItemsByEntity')->with($entity);
        $this->callTwigFunction($this->extension, 'has_workflow_items', [$entity]);
    }
}
