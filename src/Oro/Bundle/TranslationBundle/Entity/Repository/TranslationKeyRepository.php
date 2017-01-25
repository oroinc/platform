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
            ->select('COUNT(k.id)');

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

    /**
     * @return array
     */
    public function getTranslationKeysData()
    {
        $translationKeysData = $this->createQueryBuilder('tk')
            ->select('tk.id, tk.domain, tk.key')
            ->getQuery()
            ->getArrayResult();
        $translationKeys = [];
        foreach ($translationKeysData as $item) {
            $translationKeys[$item['domain']][$item['key']] = $item['id'];
        }

        return $translationKeys;
    }
}
