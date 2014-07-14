<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

class ChoiceType extends AbstractConfigType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
