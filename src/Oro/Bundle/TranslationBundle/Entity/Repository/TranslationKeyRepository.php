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

    /**
     * Returns the list of all existing in the database translation domains.
     *
     * @return array ['domain' => 'domain']
     */
    public function findAvailableDomains()
    {
        $qb = $this->createQueryBuilder('k')
            ->distinct(true)
            ->select('k.domain');

        $data = array_values(array_column($qb->getQuery()->getArrayResult(), 'domain'));

        return array_combine($data, $data);
    }
}
