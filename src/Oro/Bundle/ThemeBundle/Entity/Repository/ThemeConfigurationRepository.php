<?php

namespace Oro\Bundle\ThemeBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Doctrine repository for {@see ThemeConfiguration} entity.
 */
class ThemeConfigurationRepository extends ServiceEntityRepository
{
    public function getFieldValue(int $themeConfigId, string $fieldName): mixed
    {
        QueryBuilderUtil::checkField($fieldName);
        $qb = $this->createQueryBuilder('e');

        $row = $qb->select(QueryBuilderUtil::sprintf('e.%s', $fieldName))
            ->where('e.id = :id')
            ->setParameter('id', $themeConfigId, Types::INTEGER)
            ->getQuery()
            ->getSingleResult(AbstractQuery::HYDRATE_ARRAY);

        return $row[$fieldName] ?? null;
    }
}
