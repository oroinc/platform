<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                'workflow' => null,
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
        $view->vars['configs']['entityId'] = $options['workflow'];
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
