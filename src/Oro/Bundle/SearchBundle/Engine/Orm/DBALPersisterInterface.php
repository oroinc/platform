<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Oro\Bundle\SearchBundle\Entity\AbstractItem;

/**
 * Defines the contract for persisting search index items using DBAL.
 *
 * This interface specifies methods for writing search index items to the database
 * using Doctrine DBAL and flushing those writes in batch operations for improved
 * performance during indexing operations.
 */
interface DBALPersisterInterface
{
    public function writeItem(AbstractItem $item);

    /**
     * @return void
     */
    public function flushWrites();
}
