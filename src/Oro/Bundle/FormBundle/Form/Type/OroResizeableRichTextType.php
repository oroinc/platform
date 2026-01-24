<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * Form type for rich text editing with resizable editor.
 *
 * This type extends {@see OroRichTextType} to provide a rich text editor with resizable
 * capabilities, allowing users to adjust the editor height as needed while editing
 * formatted text content.
 */
class OroResizeableRichTextType extends AbstractType
{
    const NAME = 'oro_resizeable_rich_text';

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
