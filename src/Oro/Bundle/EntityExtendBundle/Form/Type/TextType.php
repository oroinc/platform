<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

class TextType extends AbstractConfigType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_text';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }
}
