<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Entity\Item;

/**
 * @deprecated Please use the DBALPersisterDriverTrait instead. See example in BaseDriver.
 */
class DbalStorer
{
    use DBALPersisterDriverTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->entityManager = $doctrineHelper->getEntityManager(Item::class);
    }

    /**
     * @deprecated Please use flushWrites() from the trait.
     *
     * Stores all data taken from Items given by 'addItem' method
     */
    public function store()
    {
        $this->flushWrites();
    }

    /**
     * @deprecated Please use writeItem() from the trait.
     *
     * Adds Item of which data will be stored when 'store' method is called
     *
     * @param Item $item
     */
    public function addItem(Item $item)
    {
        $this->writeItem($item);
    }
}
