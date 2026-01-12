<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

/**
 * Defines the contract for readers that retrieve batch identifiers.
 *
 * Implementations provide a way to retrieve identifiers (such as entity IDs or row numbers)
 * for a batch of items from a data source. These identifiers are used to track which items
 * have been processed and to support batch-based import/export operations.
 */
interface BatchIdsReaderInterface
{
    /**
     * Get ids, it can ids or number of rows, or smth else that can help with identification the read elements.
     *
     * @param string $sourceName
     * @param array $options
     * @return array
     */
    public function getIds($sourceName, array $options = []);
}
