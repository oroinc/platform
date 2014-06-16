<?php

namespace Oro\Bundle\AttachmentBundle\Form;

use Symfony\Component\Form\AbstractType;

class ImageType extends AbstractType
{
    const NAME = 'oro_image';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return FileType::NAME;
    }
}
