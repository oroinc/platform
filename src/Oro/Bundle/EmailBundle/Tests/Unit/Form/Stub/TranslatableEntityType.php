<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Stub;

use Symfony\Component\Form\AbstractType;

class TranslatableEntityType extends AbstractType
{
    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'translatable_entity';
    }
}