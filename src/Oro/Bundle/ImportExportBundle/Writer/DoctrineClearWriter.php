<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;

/**
 * Batch job writer that clears entity manager.
 */
class DoctrineClearWriter implements ItemWriterInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $this->registry->getManager()->clear();
    }
}
