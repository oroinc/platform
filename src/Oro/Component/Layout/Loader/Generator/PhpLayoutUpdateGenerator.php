<?php

namespace Oro\Component\Layout\Extension\Theme\Generator;

class PhpLayoutUpdateGenerator extends AbstractLayoutUpdateGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function doGenerateBody(GeneratorData $data)
    {
        return trim(str_replace(['<?php', '<?', '?>'], '', $data->getSource()));
    }
}
