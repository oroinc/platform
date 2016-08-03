<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

/**
 * This form type represents a 'textarea' association property.
 * Can be used bor both single and multiple associations.
 */
class TextareaAssociationPropertyType extends AbstractAssociationType
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
        return 'oro_entity_extend_association_property_textarea';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'textarea';
    }
}
