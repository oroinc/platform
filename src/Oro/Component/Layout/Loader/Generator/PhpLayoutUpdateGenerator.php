<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Loader\Generator;

/**
 * Generates layout updates directly from PHP code.
 */
class PhpLayoutUpdateGenerator extends AbstractLayoutUpdateGenerator
{
    protected function doGenerateBody(GeneratorData $data): string
    {
        return \str_replace(['<?php', '<?', '?>'], '', $data->getSource());
    }
}
