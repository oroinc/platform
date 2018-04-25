<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Symfony\Component\Form\AbstractType;

class OroResizeableRichTextType extends AbstractType
{
    const NAME = 'oro_resizeable_rich_text';

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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroRichTextType::class;
    }
}
