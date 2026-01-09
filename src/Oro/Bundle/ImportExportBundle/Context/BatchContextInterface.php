<?php

namespace Oro\Bundle\ImportExportBundle\Context;

/**
 * Defines the contract for accessing batch processing context information.
 *
 * This interface provides methods to retrieve batch-related metadata such as the size
 * of the current batch and the batch number, which are essential for tracking progress
 * during batch import/export operations.
 */
interface BatchContextInterface
{
    /**
     * @return int
     */
    public function getBatchSize();

    /**
     * @return int
     */
    public function getBatchNumber();
}
