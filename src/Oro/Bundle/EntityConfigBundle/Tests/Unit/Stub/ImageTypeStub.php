<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub;

use Symfony\Component\Form\AbstractType;

class ImageTypeStub extends AbstractType
{
    const NAME = 'oro_image';

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
