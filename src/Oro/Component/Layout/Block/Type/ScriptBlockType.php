<?php

namespace Oro\Component\Layout\Block\Type;

use Oro\Component\Layout\AbstractBlockType;

class ScriptBlockType extends AbstractBlockType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'container';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'script';
    }
}
