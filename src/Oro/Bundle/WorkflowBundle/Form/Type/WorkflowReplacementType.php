<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDeactivationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowReplacementType extends AbstractType
{
    const NAME = 'oro_workflow_replacement';

    /** @var WorkflowDeactivationHelper */
    protected $helper;

    public function __construct(WorkflowDeactivationHelper $helper)
    {
        $this->helper = $helper;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'workflowsToDeactivation',
            OroChoiceType::class,
            [
                'label' => 'oro.workflow.workflowdefinition.entity_plural_label',
                'configs' => [
                    'placeholder' => 'oro.workflow.workflowdefinition.placeholder.select_replacement'
                ],
                'choices' => array_flip($this->helper->getWorkflowsForManualDeactivation($options['workflow'])),
                'multiple' => true,
                'required' => false
            ]
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('workflow', null);
        $resolver->setAllowedTypes('workflow', [WorkflowDefinition::class]);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var WorkflowDefinition $workflow */
        $workflow = $options['workflow'];

        $view->vars['workflowsToDeactivation'] = $this->helper->getWorkflowsToDeactivation($workflow)->getValues();
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
