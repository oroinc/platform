<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Twig;

use Oro\Bundle\WorkflowBundle\Formatter\WorkflowVariableFormatter;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManagerRegistry;
use Oro\Bundle\WorkflowBundle\Twig\WorkflowExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private WorkflowManager&MockObject $workflowManager;
    private WorkflowExtension $extension;

    #[\Override]
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

    public function testHasApplicableWorkflows(): void
    {
        $entity = new \stdClass();
        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflows')
            ->with(self::identicalTo($entity));
        $this->callTwigFunction($this->extension, 'has_workflows', [$entity]);
    }

    public function testHasWorkflowItemsByEntity(): void
    {
        $entity = new \stdClass();
        $this->workflowManager->expects($this->once())
            ->method('hasWorkflowItemsByEntity')
            ->with(self::identicalTo($entity));
        $this->callTwigFunction($this->extension, 'has_workflow_items', [$entity]);
    }

    public function testFormatWorkflowVariableValue(): void
    {
        $entity = $this->createMock(Variable::class);
        $this->callTwigFilter($this->extension, 'oro_format_workflow_variable_value', [$entity]);
    }
}
