<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

/**
 * Filter out import directives.
 */
interface ImportFilterInterface
{
    public function filter(array $imports): array;
}
