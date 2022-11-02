<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;

/**
 * Batch job writer that skips writing operation.
 */
class DummyWriter implements ItemWriterInterface
{
    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
    }
}
