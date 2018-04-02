<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\FormBundle\Form\Type\OroIconType;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowDefinitionChoicesGroupProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class WorkflowDefinitionType extends AbstractType
{
    const NAME = 'oro_workflow_definition';

    /** @var WorkflowDefinitionChoicesGroupProvider */
    private $provider;

    public function __construct(WorkflowDefinitionChoicesGroupProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'label',
                TextType::class,
                [
                    'label' => 'oro.workflow.workflowdefinition.label.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                    'tooltip' => 'oro.workflow.workflowdefinition.name.description'
                ]
            )
            ->add(
                'related_entity',
                ApplicableEntitiesType::class,
                [
                    'label' => 'oro.workflow.workflowdefinition.related_entity.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                    'tooltip' => 'oro.workflow.workflowdefinition.related_entity.description'
                ]
            )
            ->add(
                'steps_display_ordered',
                CheckboxType::class,
                [
                    'label' => 'oro.workflow.workflowdefinition.steps_display_ordered.label',
                    'required' => false,
                    'tooltip' => 'oro.workflow.workflowdefinition.steps_display_ordered.description'
                ]
            )
            ->add(
                'transition_prototype_icon',
                OroIconType::class,
                [
                    'label' => 'oro.workflow.form.button_icon.label',
                    'mapped' => false,
                    'required' => false,
                    'tooltip' => 'oro.workflow.workflowdefinition.transition.icon.tooltip'
                ]
            )
            ->add(
                'exclusive_active_groups',
                OroChoiceType::class,
                [
                    'choices' => $this->provider->getActiveGroupsChoices(),
                    'label' => 'oro.workflow.workflowdefinition.exclusive_active_groups.label',
                    'required' => false,
                    'multiple' => true,
                    'tooltip' => 'oro.workflow.form.exclusive_active_groups.tooltip'
                ]
            )
            ->add(
                'exclusive_record_groups',
                OroChoiceType::class,
                [
                    'choices' => $this->provider->getRecordGroupsChoices(),
                    'label' => 'oro.workflow.workflowdefinition.exclusive_record_groups.label',
                    'required' => false,
                    'multiple' => true,
                    'tooltip' => 'oro.workflow.form.exclusive_record_groups.tooltip'
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
