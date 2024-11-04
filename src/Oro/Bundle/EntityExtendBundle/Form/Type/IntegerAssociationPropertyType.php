<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * This form type represents a 'integer' association property.
 * Can be used bor both single and multiple associations.
 */
class IntegerAssociationPropertyType extends AbstractAssociationType
{
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_association_property_integer';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return IntegerType::class;
    }
}
