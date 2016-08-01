<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

/**
 * This form type represents a 'text' association property.
 * Can be used bor both single and multiple associations.
 */
class TextAssociationPropertyType extends AbstractAssociationType
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
        return 'oro_entity_extend_association_property_text';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }
}
