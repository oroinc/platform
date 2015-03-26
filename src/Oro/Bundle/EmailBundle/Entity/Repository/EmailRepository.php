<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class EmailRepository extends EntityRepository
{
    /**
     * Gets emails by ids
     *
     * @param array $ids
     * @return array
     */
    public function findByIds($ids)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->createQueryBuilder('e');
        $criteria = new Criteria();
        $criteria->where($criteria->expr()->in('id', $ids));
        $criteria->orderBy(['sentAt' => Criteria::DESC]);
        $queryBuilder->addCriteria($criteria);
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }
}
