<?php

namespace Oro\Component\Layout\Loader\Generator;

use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

/**
 * Defines the contract for extending the configuration-based layout update generator.
 *
 * Extensions implement this interface to scan generator data and add appropriate visitors
 * to the visitor collection for processing layout update configurations.
 */
interface ConfigLayoutUpdateGeneratorExtensionInterface
{
    /**
     * Scans the given GeneratorData and add appropriate visitor to the collection of visitors.
     */
    public function prepare(GeneratorData $data, VisitorCollection $visitorCollection);
}
