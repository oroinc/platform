<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

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
