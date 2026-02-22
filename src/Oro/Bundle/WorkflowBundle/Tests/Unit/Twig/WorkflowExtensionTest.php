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

    private WorkflowVariableFormatter&MockObject $workflowVariableFormatter;
    private WorkflowManager&MockObject $workflowManager;
    private WorkflowExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowVariableFormatter = $this->createMock(WorkflowVariableFormatter::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $workflowManagerRegistry = $this->createMock(WorkflowManagerRegistry::class);
        $workflowManagerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->workflowManager);

        $container = self::getContainerBuilder()
            ->add(WorkflowVariableFormatter::class, $this->workflowVariableFormatter)
            ->add(WorkflowManagerRegistry::class, $workflowManagerRegistry)
            ->getContainer($this);

        $this->extension = new WorkflowExtension($container);
    }

    public function testHasApplicableWorkflows(): void
    {
        $entity = new \stdClass();

        $this->workflowManager->expects($this->once())
            ->method('hasApplicableWorkflows')
            ->with(self::identicalTo($entity))
            ->willReturn(true);

        self::assertTrue(
            self::callTwigFunction($this->extension, 'has_workflows', [$entity])
        );
    }

    public function testHasWorkflowItemsByEntity(): void
    {
        $entity = new \stdClass();

        $this->workflowManager->expects($this->once())
            ->method('hasWorkflowItemsByEntity')
            ->with(self::identicalTo($entity))
            ->willReturn(true);

        self::assertTrue(
            self::callTwigFunction($this->extension, 'has_workflow_items', [$entity])
        );
    }

    public function testFormatWorkflowVariableValue(): void
    {
        $variable = $this->createMock(Variable::class);
        $formattedVariable = 'formatted variable';

        $this->workflowVariableFormatter->expects($this->once())
            ->method('formatWorkflowVariableValue')
            ->with(self::identicalTo($variable))
            ->willReturn($formattedVariable);

        self::assertEquals(
            $formattedVariable,
            self::callTwigFilter($this->extension, 'oro_format_workflow_variable_value', [$variable])
        );
    }
}
