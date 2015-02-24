<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

class PhpLayoutUpdateGenerator extends AbstractLayoutUpdateGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function doGenerateBody(GeneratorData $data)
    {
        $result = trim(str_replace(['<?php', '<?', '?>'], '', $data->getSource()));

        if ($data->getFilename()) {
            $result = '// filename: ' . $data->getFilename() . "\n" . $result;
        }

        return $result;
    }
}
