<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * This form type represents a 'choice' association property.
 * Can be used bor both single and multiple associations.
 */
class ChoiceAssociationPropertyType extends AbstractAssociationType
{
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_extend_association_property_choice';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
