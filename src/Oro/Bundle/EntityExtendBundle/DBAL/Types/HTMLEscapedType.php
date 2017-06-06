<?php

namespace Oro\Bundle\EntityExtendBundle\DBAL\Types;

use Doctrine\DBAL\Types\TextType;

class HTMLEscapedType extends TextType
{
    const TYPE = 'html_escaped';

    /** {@inheritdoc} */
    public function getName()
    {
        return self::TYPE;
    }
}
