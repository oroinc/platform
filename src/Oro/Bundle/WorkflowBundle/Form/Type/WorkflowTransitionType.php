<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowed;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for workflow transition forms with attribute handling and validation.
 *
 * This form type extends workflow attributes form to provide transition-specific functionality,
 * including validation that the transition is allowed for the current workflow item.
 */
class WorkflowTransitionType extends AbstractType
{
    public const NAME = 'oro_workflow_transition';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return WorkflowAttributesType::class;
    }

    /**
     * Custom options:
     * - "workflow_item" - required, instance of WorkflowItem entity
     * - "transition_name" - required, name of transition
     *
     */
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array('workflow_item', 'transition_name'));

        $resolver->setAllowedTypes('transition_name', 'string');

        $resolver->setNormalizer(
            'constraints',
            function (Options $options, $constraints) {
                if (!$constraints) {
                    $constraints = [];
                }

                $constraints[] = new TransitionIsAllowed(
                    $options['workflow_item'],
                    $options['transition_name']
                );

                return $constraints;
            }
        );
    }
}
