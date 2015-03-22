<?php

namespace Oro\Component\Layout\Extension\Theme\Generator;

use Oro\Component\Layout\Extension\Theme\Generator\Visitor\VisitorCollection;

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
