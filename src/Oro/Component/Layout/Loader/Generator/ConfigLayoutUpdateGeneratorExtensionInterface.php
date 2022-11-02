<?php

namespace Oro\Component\Layout\Loader\Generator;

use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

interface ConfigLayoutUpdateGeneratorExtensionInterface
{
    /**
     * Scans the given GeneratorData and add appropriate visitor to the collection of visitors.
     */
    public function prepare(GeneratorData $data, VisitorCollection $visitorCollection);
}
