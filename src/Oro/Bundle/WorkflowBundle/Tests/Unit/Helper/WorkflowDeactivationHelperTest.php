<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDeactivationHelper;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowDeactivationHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowRegistry;

    /** @var WorkflowTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationHelper;

    /** @var WorkflowDeactivationHelper */
    protected $helper;

    protected function setUp()
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->translationHelper = $this->createMock(WorkflowTranslationHelper::class);

        $this->helper = new WorkflowDeactivationHelper($this->workflowRegistry, $this->translationHelper);
    }

    public function testGetWorkflowsForManualDeactivation()
    {
        $workflow1 = $this->getWorkflow('workflow1', ['exclusiveGroup1']);
        $workflow2 = $this->getWorkflow('workflow2', ['exclusiveGroup1']);
        $workflow3 = $this->getWorkflow('workflow3');
        $workflow4 = $this->getWorkflow('workflow4');

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')
            ->with(['exclusiveGroup1'])
            ->willReturn(new ArrayCollection([$workflow1, $workflow2]));

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflows')
            ->willReturn(
                new ArrayCollection(
                    [
                        $workflow1->getName() => $workflow1,
                        $workflow2->getName() => $workflow2,
                        $workflow4->getName() => $workflow4,
                        $workflow3->getName() => $workflow3,
                    ]
                )
            );

        $this->translationHelper->expects($this->any())
            ->method('findWorkflowTranslation')
            ->willReturnMap(
                [
                    ['workflow1', 'workflow1', null, 'Workflow1 Name'],
                    ['workflow2', 'workflow2', null, 'Workflow2 Name'],
                    ['workflow3', 'workflow3', null, 'Workflow3 Name'],
                    ['workflow4', 'workflow4', null, 'Workflow4 Name'],
                ]
            );

        $this->assertEquals(
            [$workflow3->getName() => 'Workflow3 Name', $workflow4->getName() => 'Workflow4 Name'],
            $this->helper->getWorkflowsForManualDeactivation($workflow2->getDefinition())
        );
    }

    public function testGetWorkflowsToDeactivation()
    {
        $workflow1 = $this->getWorkflow('workflow1', ['exclusiveGroup1']);
        $workflow2 = $this->getWorkflow('workflow2', ['exclusiveGroup1']);

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByActiveGroups')
            ->with(['exclusiveGroup1'])
            ->willReturn(new ArrayCollection([$workflow1, $workflow2]));

        $this->assertEquals(
            new ArrayCollection([$workflow1]),
            $this->helper->getWorkflowsToDeactivation($workflow2->getDefinition())
        );
    }

    /**
     * @param string $name
     * @param array $xclusiveActiveGroups
     * @return WorkflowDefinition
     */
    protected function getWorkflowDefinition($name, array $xclusiveActiveGroups = [])
    {
        $definition = new WorkflowDefinition();
        $definition->setName($name)->setExclusiveActiveGroups($xclusiveActiveGroups);

        return $definition;
    }

    /**
     * @param string $name
     * @param array $exclusiveActiveGroups
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getWorkflow($name, array $exclusiveActiveGroups = [])
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())->method('getName')->willReturn($name);
        $workflow->expects($this->any())->method('getLabel')->willReturn($name);
        $workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($this->getWorkflowDefinition($name, $exclusiveActiveGroups));

        return $workflow;
    }
}
