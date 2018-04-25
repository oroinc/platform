<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowAttributesType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowed;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowTransitionTypeTest extends AbstractWorkflowAttributesTypeTestCase
{
    /**
     * @var WorkflowTransitionType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new WorkflowTransitionType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    WorkflowAttributesType::class => $this->createWorkflowAttributesType(),
                ],
                []
            )
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(WorkflowAttributesType::class, $this->type->getParent());
    }

    public function testBuildForm()
    {
        $builder = $this->createMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $workflowItem = new WorkflowItem();

        $transitionName = 'test';
        $transition = $this->createMock('Oro\Bundle\WorkflowBundle\Model\Transition');
        $transition->expects($this->once())->method('getName')->will($this->returnValue($transitionName));

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $restrictionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = new Workflow($doctrineHelper, $aclManager, $restrictionManager);
        $workflow->getTransitionManager()->setTransitions(array($transition));

        $options = array(
            'workflow' => $workflow,
            'workflow_item' => $workflowItem,
            'transition_name' => $transitionName,
        );
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
