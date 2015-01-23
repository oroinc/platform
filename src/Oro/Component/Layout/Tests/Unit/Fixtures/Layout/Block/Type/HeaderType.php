<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractType;

class HeaderType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'header';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'container';
    }
}
