<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Doctrine\Common\Persistence\ManagerRegistry;

class DoctrineClearWriter implements ItemWriterInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
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
