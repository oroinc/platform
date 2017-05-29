<?php

namespace Oro\Bundle\EntityExtendBundle\DBAL\Types;

use Doctrine\DBAL\Types\TextType;

class RichTextType extends TextType
{
    const TYPE = 'rich_text';

    /** {@inheritdoc} */
    public function getName()
    {
        return self::TYPE;
    }
}
