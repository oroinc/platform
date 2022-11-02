<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * The repository for TranslationKey entity.
 */
class TranslationKeyRepository extends EntityRepository
{
    /**
     * Gets the total number of translation keys.
     */
    public function getCount(): int
    {
        return (int)$this->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Gets the list of all translation domains.
     *
     * @return string[]
     */
    public function findAvailableDomains(): array
    {
        $rows = $this->createQueryBuilder('k')
            ->distinct(true)
            ->select('k.domain')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_column($rows, 'domain'));
    }

    /**
     * Gets information about translation keys grouped by domain.
     *
     * @return array [domain => [translation key => TranslationKey entity id, ...], ...]
     */
    public function getTranslationKeysData(): array
    {
        $rows = $this->createQueryBuilder('k')
            ->select('k.id, k.key, k.domain')
            ->addOrderBy('k.key')
            ->addOrderBy('k.domain')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['domain']][$row['key']] = $row['id'];
        }

        return $result;
    }
}
