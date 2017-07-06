<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;

/**
 * Factory for configuration import processors with matching method.
 * Before creation of processor isApplicable method should be called to match the import options against.
 */
interface ImportProcessorFactoryInterface
{
    /**
     * @param mixed $import
     * @return bool
     */
    public function isApplicable($import): bool;

    /**
     * @param mixed $import
     * @return ConfigImportProcessorInterface
     */
    public function create($import): ConfigImportProcessorInterface;
}
