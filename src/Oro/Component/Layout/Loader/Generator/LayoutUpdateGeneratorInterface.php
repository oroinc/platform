<?php
declare(strict_types=1);

namespace Oro\Component\Layout\Loader\Generator;

use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

/**
 * Interface for layout update generators.
 */
interface LayoutUpdateGeneratorInterface
{
    public const UPDATE_METHOD_NAME = 'updateLayout';

    public const PARAM_LAYOUT_MANIPULATOR = 'layoutManipulator';
    public const PARAM_LAYOUT_ITEM = 'item';

    /**
     * Generates a PHP class implementing instance of \Oro\Component\Layout\LayoutUpdateInterface
     * with the specified name based on the provided actions data.
     */
    public function generate(
        string $className,
        GeneratorData $data,
        ?VisitorCollection $visitorCollection = null
    ): string;

    public function getVisitorCollection(): ?VisitorCollection;
}
