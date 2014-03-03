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
                    'label' => 'Label'
                )
            )
            ->add(
                'related_entity',
                'oro_entity_choice',
                array(
                    'label' => 'Related Entity',
                )
            )
            ->add(
                'steps_display_ordered',
                'checkbox',
                array(
                    'label' => 'Display all steps in order',
                )
            )
            ->add(
                'transition_prototype_icon',
                'oro_icon_select',
                array(
                    'label' => 'Button icon',
                    'mapped' => false
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
