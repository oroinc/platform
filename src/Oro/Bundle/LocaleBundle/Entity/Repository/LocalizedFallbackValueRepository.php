<?php

namespace Oro\Bundle\LocaleBundle\Entity\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Doctrine repository for entity LocalizedFallbackValue
 */
class LocalizedFallbackValueRepository extends EntityRepository
{
    private const BATCH_SIZE = 1000;

    private ?string $unusedLocalizedFallbackValuesSql = null;

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

    public function getUnusedLocalizedFallbackValueIds(array $classesMetadata): array
    {
        if ($this->unusedLocalizedFallbackValuesSql === null) {
            $this->unusedLocalizedFallbackValuesSql = $this->getUnusedLocalizedFallbackValuesSqlQuery($classesMetadata);
        }


        return $this->getEntityManager()
            ->getConnection()
            ->fetchFirstColumn(
                $this->unusedLocalizedFallbackValuesSql,
                [
                    'batchSize' => self::BATCH_SIZE,
                ],
                [
                    'batchSize' => Types::INTEGER,
                ]
            );
    }

    private function getUnusedLocalizedFallbackValuesSqlQuery(array $classesMetadata): string
    {
        $usedLocalizedFallbackValuesSqlQueries = [];

        foreach ($classesMetadata as $classMetadata) {
            $classTableName = $classMetadata->getTableName();
            $associationMappings = $classMetadata->getAssociationMappings();
            foreach ($associationMappings as $associationMapping) {
                $usedLocalizedFallbackValuesSqlQuery = $this->getUsedLocalizedFallbackValuesQuery(
                    $associationMapping,
                    $classTableName
                );
                if ($usedLocalizedFallbackValuesSqlQuery) {
                    $usedLocalizedFallbackValuesSqlQueries[] = $usedLocalizedFallbackValuesSqlQuery;
                }
            }
        }

        return sprintf(
            <<<SQL
SELECT id
FROM oro_fallback_localization_val AS lfv
WHERE NOT EXISTS (%1\$s)
LIMIT :batchSize OFFSET 0
SQL,
            implode(' UNION ', $usedLocalizedFallbackValuesSqlQueries)
        );
    }

    private function getUsedLocalizedFallbackValuesQuery(
        array $associationMapping,
        string $classTableName
    ): ?string {
        if ($associationMapping['targetEntity'] !== LocalizedFallbackValue::class) {
            return null;
        }

        return match ($associationMapping['type']) {
            ClassMetadataInfo::MANY_TO_MANY => sprintf(
                <<<SQL
(
SELECT %1\$s
FROM %2\$s
WHERE lfv.id = %2\$s.%1\$s
)
SQL,
                $associationMapping['joinTable']['inverseJoinColumns'][0]['name'],
                $associationMapping['joinTable']['name']
            ),
            ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE => sprintf(
                <<<SQL
(
SELECT %1\$s
FROM %2\$s
WHERE lfv.id = %2\$s.%1\$s
)
SQL,
                $associationMapping['joinColumns'][0]['name'],
                $classTableName
            ),
            ClassMetadataInfo::ONE_TO_MANY => sprintf(
                <<<SQL
(
SELECT id
FROM oro_fallback_localization_val
WHERE lfv.id = id AND %1\$s IS NOT NULL
)
SQL,
                $associationMapping['mappedBy'].'_id'
            ),
            default => null,
        };
    }

    public function deleteByIds(array $ids): int
    {
        $qb = $this->createQueryBuilder('lfv');

        return $qb->delete()
            ->where($qb->expr()->in('lfv.id', ':ids'))
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->execute();
    }
}
