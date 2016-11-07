<?php

namespace Oro\Bundle\WorkflowBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowed;

class WorkflowTransitionType extends AbstractType
{
    const NAME = 'oro_workflow_transition';

    /**
     * {@inheritDoc}
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
        return WorkflowAttributesType::NAME;
    }

    /**
     * Custom options:
     * - "workflow_item" - required, instance of WorkflowItem entity
     * - "transition_name" - required, name of transition
     *
     * {@inheritdoc}
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array('workflow_item', 'transition_name'));

        $resolver->setAllowedTypes(
            array(
                'transition_name' => 'string',
            )
        );

        $resolver->setNormalizer('constraints', function (Options $options, $constraints) {
            if (!$constraints) {
                $constraints = array();
            }

            $constraints[] = new TransitionIsAllowed(
                $options['workflow_item'],
                $options['transition_name']
            );

            return $constraints;
        });
    }
}
