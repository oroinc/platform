<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub;

use Symfony\Component\Form\AbstractType;

class ImageTypeStub extends AbstractType
{
    public const NAME = 'oro_image';

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
