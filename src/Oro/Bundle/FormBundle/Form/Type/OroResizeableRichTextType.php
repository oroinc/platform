<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class OroResizeableRichTextType extends AbstractType
{
    public const NAME = 'oro_resizeable_rich_text';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroRichTextType::class;
    }
}
