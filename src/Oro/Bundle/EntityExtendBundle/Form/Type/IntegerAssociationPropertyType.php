<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

/**
 * This form type represents a 'integer' association property.
 * Can be used bor both single and multiple associations.
 */
class IntegerAssociationPropertyType extends AbstractAssociationType
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
        return 'oro_entity_extend_association_property_integer';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'integer';
    }
}
