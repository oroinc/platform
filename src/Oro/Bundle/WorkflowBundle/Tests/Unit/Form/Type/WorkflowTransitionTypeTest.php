<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowAttributesType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowed;

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

    protected function getExtensions()
    {
        return array(
            new PreloadedExtension(
                array(
                    WorkflowAttributesType::NAME => $this->createWorkflowAttributesType(),
                ),
                array()
            )
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_workflow_transition', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_workflow_attributes', $this->type->getParent());
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $workflowItem = new WorkflowItem();

        $transitionName = 'test';
        $transition = $this->getMock('Oro\Bundle\WorkflowBundle\Model\Transition');
        $transition->expects($this->once())->method('getName')->will($this->returnValue($transitionName));

        $entityConnector = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\EntityConnector')
            ->disableOriginalConstructor()
            ->getMock();
        $aclManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Acl\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $restrictionManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = new Workflow($entityConnector, $aclManager, $restrictionManager);
        $workflow->getTransitionManager()->setTransitions(array($transition));

        $options = array(
            'workflow' => $workflow,
            'workflow_item' => $workflowItem,
            'transition_name' => $transitionName,
        );
        $this->type->buildForm($builder, $options);
    }

    public function testSetDefaultOptions()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['constraints' => null]);

        $workflowItem = new WorkflowItem();
        $transitionName = 'TransitionName';

        $this->type->setDefaultOptions($resolver);
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
