<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

/**
 * Provides statuses of the processing result for batch items.
 */
final class BatchUpdateItemStatus
{
    /**
     * The item is not processed yet.
     */
    public const NOT_PROCESSED = 0;

    /**
     * No any errors are detected when processing the item.
     */
    public const NO_ERRORS = 1;

    /**
     * Some errors are detected when processing the item,
     * but it is possible that the item will be processed without any errors at the next try.
     */
    public const HAS_ERRORS = 2;

    /**
     * Some permanent errors are detected when processing the item,
     * it is no any sense to process the item again.
     * An example of such errors is a validation constraint violation for an entity.
     */
    public const HAS_PERMANENT_ERRORS = 3;
}
