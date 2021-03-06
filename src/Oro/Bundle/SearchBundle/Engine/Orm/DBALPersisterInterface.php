<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Oro\Bundle\SearchBundle\Entity\AbstractItem;

interface DBALPersisterInterface
{
    public function writeItem(AbstractItem $item);

    /**
     * @return void
     */
    public function flushWrites();
}
