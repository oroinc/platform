<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowAttributesType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowed;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowTransitionTypeTest extends AbstractWorkflowAttributesTypeTestCase
{
    private WorkflowTransitionType $type;

    protected function setUp(): void
    {
        $this->type = new WorkflowTransitionType();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                $this->type,
                $this->createWorkflowAttributesType()
            ], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(WorkflowAttributesType::class, $this->type->getParent());
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $workflowItem = new WorkflowItem();

        $transitionName = 'test';
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getName')
            ->willReturn($transitionName);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $aclManager = $this->createMock(AclManager::class);
        $restrictionManager = $this->createMock(RestrictionManager::class);

        $workflow = new Workflow($doctrineHelper, $aclManager, $restrictionManager);
        $workflow->getTransitionManager()->setTransitions([$transition]);

        $options = [
            'workflow' => $workflow,
            'workflow_item' => $workflowItem,
            'transition_name' => $transitionName,
        ];
        $this->type->buildForm($builder, $options);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['constraints' => null]);

        $workflowItem = new WorkflowItem();
        $transitionName = 'TransitionName';

        $this->type->configureOptions($resolver);
        $result = $resolver->resolve(
            [
                'workflow_item' => $workflowItem,
                'transition_name' => $transitionName
            ]
        );
        $this->assertEquals(
            [
                'workflow_item' => $workflowItem,
                'transition_name' => $transitionName,
                'constraints' => [
                    new TransitionIsAllowed($workflowItem, $transitionName)
                ]
            ],
            $result
        );
    }
}
