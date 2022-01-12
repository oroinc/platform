<?php

namespace Oro\Bundle\LocaleBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Doctrine repository for entity LocalizedFallbackValue
 */
class LocalizedFallbackValueRepository extends EntityRepository
{
    public function getParentIdByFallbackValue(
        string $targetClass,
        string $field,
        LocalizedFallbackValue $sourceEntity
    ): ?int {
        $queryBuilder = $this->getEntityManager()->getRepository($targetClass)->createQueryBuilder('_te');

        $queryBuilder
            ->select('_te.id as id')
            ->join(QueryBuilderUtil::getField('_te', $field), '_tef')
            ->where($queryBuilder->expr()->eq('_tef', ':localFallbackEntity'))
            ->setParameter('localFallbackEntity', $sourceEntity)
            ->setMaxResults(1)
        ;

        $result = $queryBuilder->getQuery()->getResult();

        return $result[0]['id'] ?? null;
    }
}
