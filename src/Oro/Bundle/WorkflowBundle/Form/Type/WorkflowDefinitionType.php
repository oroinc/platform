<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroIconType;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class WorkflowDefinitionType extends AbstractType
{
    const NAME = 'oro_workflow_definition';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'label',
                'text',
                [
                    'label' => 'oro.workflow.workflowdefinition.label.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                    'tooltip' => 'oro.workflow.workflowdefinition.name.description'
                ]
            )
            ->add(
                'related_entity',
                ApplicableEntitiesType::NAME,
                [
                    'label' => 'oro.workflow.workflowdefinition.related_entity.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                    'tooltip' => 'oro.workflow.workflowdefinition.related_entity.description'
                ]
            )
            ->add(
                'steps_display_ordered',
                'checkbox',
                [
                    'label' => 'oro.workflow.workflowdefinition.steps_display_ordered.label',
                    'required' => false,
                    'tooltip' => 'oro.workflow.workflowdefinition.steps_display_ordered.description'
                ]
            )
            ->add(
                'transition_prototype_icon',
                OroIconType::NAME,
                [
                    'label' => 'oro.workflow.form.button_icon.label',
                    'mapped' => false,
                    'required' => false,
                    'tooltip' => 'oro.workflow.workflowdefinition.transition.icon.tooltip'
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => WorkflowDefinition::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
