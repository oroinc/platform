<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType as ParentTextType;

/**
 * This form type is just a wrapper around standard 'text' form type, but
 * this form type can handle 'immutable' behaviour. It means that you can use it
 * if you need to disable changing of an attribute value in case if there is
 * 'immutable' attribute set to true in the same config scope as your attribute.
 */
class TextType extends AbstractConfigType
{
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_config_text';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ParentTextType::class;
    }
}
