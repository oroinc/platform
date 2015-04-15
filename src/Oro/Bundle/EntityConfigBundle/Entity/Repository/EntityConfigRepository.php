<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class EntityConfigRepository extends EntityRepository
{
    /**
     * @param array $classNames
     * @return array
     */
    public function findEntitiesByClassNames(array $classNames)
    {
        $qb = $this->createQueryBuilder('e')
            ->select();
        $qb->where($qb->expr()->in('e.className', $classNames));

        return $qb->getQuery()->getResult();
    }
}
