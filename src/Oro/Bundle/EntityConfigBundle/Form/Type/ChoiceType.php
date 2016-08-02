<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

/**
 * This form type is just a wrapper around standard 'choice' form type, but
 * this form type can handle 'immutable' behaviour. It means that you can use it
 * if you need to disable changing of an attribute value in case if there is
 * 'immutable' attribute set to true in the same config scope as your attribute.
 */
class ChoiceType extends AbstractConfigType
{
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
        return 'oro_entity_config_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
