<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

class PhpLayoutUpdateGenerator extends AbstractLayoutUpdateGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function doGenerateBody($data)
    {
        return str_replace(['<?php', '<?', '?>'], '', $data);
    }
}
