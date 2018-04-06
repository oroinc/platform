<?php

namespace Oro\Bundle\ActionBundle\Form\Type;

use Oro\Bundle\ActionBundle\Model\Operation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type with enabled csrf protection for Operation execution.
 * Require 'csrf_token_id' option to be set when creating form with this type.
 */
class OperationExecutionType extends AbstractType
{
    const CSRF_TOKEN_FIELD = 'operation_execution_csrf_token';

    const NAME = 'oro_action_operation_execution';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'      => Operation::class,
                'csrf_protection' => ['enabled' => true],
                'csrf_field_name' => self::CSRF_TOKEN_FIELD,
                'csrf_token_id'   => null
            ]
        );
        $resolver->setRequired(['csrf_token_id']);
        $resolver->setDefined(['csrf_protection']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
