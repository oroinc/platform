<?php

namespace Oro\Component\Layout\Loader\Generator\Extension;

use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

class ImportsLayoutUpdateExtension implements ConfigLayoutUpdateGeneratorExtensionInterface
{
    const NODE_IMPORTS = 'imports';

    /**
     * {@inheritdoc}
     */
    public function prepare(GeneratorData $data, VisitorCollection $visitorCollection)
    {
        $source = $data->getSource();

        if (!empty($source[self::NODE_IMPORTS])) {
            $visitorCollection->append(new ImportsLayoutUpdateVisitor($source[self::NODE_IMPORTS]));
        }
    }
}
