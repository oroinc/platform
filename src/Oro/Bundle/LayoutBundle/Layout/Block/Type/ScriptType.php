<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractContainerType;

class ScriptType extends AbstractContainerType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'script';
    }
}
