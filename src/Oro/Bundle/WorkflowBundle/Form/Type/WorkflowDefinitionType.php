<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WorkflowDefinitionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'label',
                'text',
                array(
                    'label' => 'oro.workflow.workflowdefinition.label.label',
                    'required' => true,
                    'constraints' => array(array('NotBlank' => null)),
                    'tooltip' => 'oro.workflow.workflowdefinition.name.description'
                )
            )
            ->add(
                'related_entity',
                ApplicableEntitiesType::NAME,
                array(
                    'label' => 'oro.workflow.workflowdefinition.related_entity.label',
                    'required' => true,
                    'constraints' => array(array('NotBlank' => null)),
                    'tooltip' => 'oro.workflow.workflowdefinition.related_entity.description'
                )
            )
            ->add(
                'steps_display_ordered',
                'checkbox',
                array(
                    'label' => 'oro.workflow.workflowdefinition.steps_display_ordered.label',
                    'required' => false,
                    'tooltip' => 'oro.workflow.workflowdefinition.steps_display_ordered.description'
                )
            )
            ->add(
                'transition_prototype_icon',
                'oro_icon_select',
                array(
                    'label' => 'oro.workflow.form.button_icon.label',
                    'mapped' => false,
                    'tooltip' => 'oro.workflow.workflowdefinition.transition.icon.tooltip'
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_workflow_definition';
    }
}
