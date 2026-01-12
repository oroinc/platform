<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

/**
 * Defines the contract for writers that support cleanup of outdated records.
 *
 * Classes implementing this interface can remove outdated or superseded records
 * from the database during import operations, such as deleting old versions of
 * entities that are being replaced by new import data.
 */
interface CleanUpInterface
{
    /**
     * Remove outdated records.
     */
    public function cleanUp(array $item);
}
