<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractType;

class ButtonSeparatorType extends AbstractType
{
    const NAME = 'button_separator';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
