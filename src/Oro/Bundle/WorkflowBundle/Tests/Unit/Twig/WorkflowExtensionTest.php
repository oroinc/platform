<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Twig;

use Oro\Bundle\WorkflowBundle\Formatter\WorkflowVariableFormatter;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\StubEntity;
use Oro\Bundle\WorkflowBundle\Twig\WorkflowExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class WorkflowExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var WorkflowExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $workflowManagerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->workflowManager);

        $workflowVariableFormatter = $this->createMock(WorkflowVariableFormatter::class);
        $workflowVariableFormatter->expects($this->any())
            ->method('formatWorkflowVariableValue')
            ->willReturn('test');

        $container = self::getContainerBuilder()
            ->add(WorkflowManagerRegistry::class, $workflowManagerRegistry)
            ->add(WorkflowVariableFormatter::class, $workflowVariableFormatter)
            ->getContainer($this);

        $this->extension = new WorkflowExtension($container);
    }

    public function testHasApplicableWorkflows()
    {
        $entity = new StubEntity();
        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflows')
            ->with($entity);
        $this->callTwigFunction($this->extension, 'has_workflows', [$entity]);
    }

    public function testHasWorkflowItemsByEntity()
    {
        $entity = new StubEntity();
        $this->workflowManager->expects($this->once())
            ->method('hasWorkflowItemsByEntity')
            ->with($entity);
        $this->callTwigFunction($this->extension, 'has_workflow_items', [$entity]);
    }

    public function testFormatWorkflowVariableValue()
    {
        $entity = $this->createMock(Variable::class);
        $this->callTwigFilter($this->extension, 'oro_format_workflow_variable_value', [$entity]);
    }
}
