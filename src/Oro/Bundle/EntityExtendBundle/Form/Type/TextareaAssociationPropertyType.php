<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * This form type represents a 'textarea' association property.
 * Can be used bor both single and multiple associations.
 */
class TextareaAssociationPropertyType extends AbstractAssociationType
{
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_association_property_textarea';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return TextareaType::class;
    }
}
