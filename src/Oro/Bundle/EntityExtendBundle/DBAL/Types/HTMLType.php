<?php

namespace Oro\Bundle\EntityExtendBundle\DBAL\Types;

use Doctrine\DBAL\Types\TextType;

class HTMLType extends TextType
{
    const TYPE = 'html';

    /** {@inheritdoc} */
    public function getName()
    {
        return self::TYPE;
    }
}
