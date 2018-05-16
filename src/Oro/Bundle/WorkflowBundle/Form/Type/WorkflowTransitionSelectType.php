<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class WorkflowTransitionSelectType extends AbstractType
{
    const NAME = 'oro_workflow_transition_select';

    /** @var WorkflowRegistry $workflowRegistry */
    protected $workflowRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(WorkflowRegistry $workflowRegistry, TranslatorInterface $translator)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('workflowName');
        $resolver->setAllowedTypes('workflowName', ['string', 'null']);
        $resolver->setRequired('workflowName');

        $resolver->setNormalizer(
            'choices',
            function (Options $options, $choices) {
                if (!empty($choices) || !$options['workflowName']) {
                    return $choices;
                }

                $workflow = $this->workflowRegistry->getWorkflow($options['workflowName'], false);
                $transitions = $workflow->getTransitionManager()->getTransitions();

                $choices = [];
                foreach ($transitions as $transition) {
                    $choices[$transition->getLabel()] = $transition->getName();
                }

                return $choices;
            }
        );
    }
    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            $translatedLabel = $this->translator->trans(
                $choiceView->label,
                [],
                WorkflowTranslationHelper::TRANSLATION_DOMAIN
            );
            $choiceView->label = sprintf('%s (%s)', $translatedLabel, $choiceView->value);
        }
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

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return OroChoiceType::class;
    }
}
