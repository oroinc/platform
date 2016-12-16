<?php

namespace Oro\Bundle\EntityExtendBundle\Layout\Block\Type;

class AttributeTextType extends AbstractAttributeType
{

    const NAME = 'attribute_text';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
