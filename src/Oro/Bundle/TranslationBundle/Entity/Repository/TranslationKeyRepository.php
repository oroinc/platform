<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class TranslationKeyRepository extends EntityRepository
{
    /**
     * @return int
     */
    public function getCount()
    {
        $qb = $this->createQueryBuilder('k')
            ->select('count(k.id)');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}
