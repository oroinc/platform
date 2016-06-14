<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowReplacementSelectType extends AbstractType
{
    const NAME = 'oro_workflow_replacement_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_workflow_replacement',
                'configs' => [
                    'multiple' => true,
                    'component' => 'workflow-replacement',
                    'placeholder' => 'oro.workflow.workflowdefinition.placeholder.select_replacement',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /* @var $data WorkflowDefinition */
        $parentData = $form->getParent()->getData();

        $view->vars['configs']['entityId'] = $parentData instanceof WorkflowDefinition ? $parentData->getName() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
