<?php

namespace Oro\Component\Layout\Loader\Generator;

use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

interface ConfigLayoutUpdateGeneratorExtensionInterface
{
    /**
     * Scans the given source and add appropriate visitor to the collection of visitors.
     *
     * @param array             $source
     * @param VisitorCollection $visitorCollection
     */
    public function prepare(array $source, VisitorCollection $visitorCollection);
}
