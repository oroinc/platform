<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * This form type represents a 'text' association property.
 * Can be used bor both single and multiple associations.
 */
class TextAssociationPropertyType extends AbstractAssociationType
{
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_association_property_text';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return TextType::class;
    }
}
