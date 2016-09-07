<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;

class ItemRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return Item[]
     */
    public function getItemsByIds(array $ids)
    {
        $itemsQueryBuilder = $this
            ->createQueryBuilder('i')
            ->select('i');

        if (count($ids) > 0) {
            $itemsQueryBuilder
                ->where($itemsQueryBuilder->expr()->in('i', ':item_ids'))
                ->setParameter('item_ids', $ids);
        }

        return $itemsQueryBuilder->getQuery()->getResult();
    }
}
