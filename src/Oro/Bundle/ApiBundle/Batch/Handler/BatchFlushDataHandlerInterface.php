<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

/**
 * Represents a handler that is used to flush data in a batch operation.
 *
 * The following algorithm is used to flush data:
 * * startFlushData()
 * * prepare data to flush
 * * flushData()
 * * finishFlushData()
 * * clear()
 *
 * If some errors are detected on the prepare data to flush stage, than the following algorithm is used:
 * * startFlushData()
 * * prepare data to flush
 * * finishFlushData()
 * * clear()
 */
interface BatchFlushDataHandlerInterface
{
    /**
     * Starts the flush data operation.
     *
     * @param BatchUpdateItem[] $items
     */
    public function startFlushData(array $items): void;

    /**
     * Flushes data to a storage, e.g. to the database.
     *
     * @param BatchUpdateItem[] $items
     */
    public function flushData(array $items): void;

    /**
     * Finishes the flush data operation.
     *
     * @param BatchUpdateItem[] $items
     */
    public function finishFlushData(array $items): void;

    /**
     * Clears the state of the handler.
     */
    public function clear(): void;
}
