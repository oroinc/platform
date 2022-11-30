<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * Provides methods to retrieve information about FieldConfigModel entity
 */
class FieldConfigModelRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return FieldConfigModel[]
     */
    public function getAttributesByIds(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $attributes = $this->getBaseAttributeQueryBuilderByIds($ids)
            ->indexBy('f', 'f.id')
            ->getQuery()
            ->getResult();

        return $attributes;
    }

    /**
     * @param array $ids
     * @return FieldConfigModel[]
     */
    public function getAttributesByIdsWithIndex(array $ids)
    {
        $attributes = $this->getBaseAttributeQueryBuilderByIds($ids)
            ->indexBy('f', 'f.id')
            ->getQuery()
            ->getResult();

        return $attributes;
    }

    /**
     * @param string $className
     * @return FieldConfigModel[]
     */
    public function getAttributesByClass($className)
    {
        $attributes = $this->getBaseAttributeQueryBuilderByClass($className)
            ->getQuery()
            ->getResult();

        return $attributes;
    }

    /**
     * @param string $className
     * @return FieldConfigModel[]
     */
    public function getActiveAttributesByClass($className)
    {
        $attributes = $this->getActiveAttributesByClassQueryBuilder($className)
            ->getQuery()
            ->getResult();

        return $attributes;
    }

    public function getActiveAttributesByClassQueryBuilder(string $className): QueryBuilder
    {
        $queryBuilder = $this->getBaseAttributeQueryBuilderByClass($className)
            ->innerJoin(
                'f.indexedValues',
                's',
                'WITH',
                's.scope = :extendScope AND s.code = :stateCode AND s.value = :stateValue'
            )
            ->setParameter('stateCode', 'state')
            ->setParameter('extendScope', 'extend')
            ->setParameter('stateValue', ExtendScope::STATE_ACTIVE);

        return $queryBuilder;
    }

    /**
     * @param string $className
     * @param bool $isSystem
     * @return FieldConfigModel[]
     */
    public function getAttributesByClassAndIsSystem($className, $isSystem)
    {
        $attributes = $this->getBaseAttributeQueryBuilderByClass($className)
            ->innerJoin(
                'f.indexedValues',
                's',
                'WITH',
                's.scope = :extendScope AND s.code = :ownerCode AND s.value = :ownerValue'
            )
            ->setParameter('ownerCode', 'owner')
            ->setParameter('extendScope', 'extend')
            ->setParameter('ownerValue', ($isSystem ? ExtendScope::OWNER_SYSTEM : ExtendScope::OWNER_CUSTOM))
            ->getQuery()
            ->getResult();

        return $attributes;
    }

    public function getSortableOrFilterableAttributes(string $className, array $attributes = []): array
    {
        $queryBuilder = $this->getActiveAttributesByClassQueryBuilder($className);

        $queryBuilder
            ->leftJoin('f.indexedValues', 'so', 'WITH', 'so.code = :soCode AND so.value = :soValue')
            ->setParameter('soCode', 'sortable')
            ->setParameter('soValue', '1');

        $queryBuilder
            ->leftJoin('f.indexedValues', 'fi', 'WITH', 'fi.code = :fiCode AND fi.value = :fiValue')
            ->setParameter('fiCode', 'filterable')
            ->setParameter('fiValue', '1');

        $queryBuilder
            ->andWhere('so.value = :soValue OR fi.value = :fiValue');

        if ($attributes) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in('f.id', ':attributes'))
                ->setParameter('attributes', $attributes);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array $ids
     * @return QueryBuilder
     */
    private function getBaseAttributeQueryBuilderByIds(array $ids)
    {
        $queryBuilder = $this->getBaseAttributeQueryBuilder()
            ->andWhere('f.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $queryBuilder;
    }

    /**
     * @param string $className
     * @return QueryBuilder
     */
    private function getBaseAttributeQueryBuilderByClass($className)
    {
        $queryBuilder = $this->getBaseAttributeQueryBuilder()
            ->innerJoin('f.entity', 'e')
            ->andWhere('e.className = :className')
            ->setParameter('className', $className);

        return $queryBuilder;
    }

    /**
     * @return QueryBuilder
     */
    private function getBaseAttributeQueryBuilder()
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->innerJoin(
                'f.indexedValues',
                'i',
                'WITH',
                'i.scope = :attributeScope AND i.code = :isAttributeCode AND i.value = :isAttributeValue'
            )
            ->leftJoin(
                'f.indexedValues',
                'state',
                'WITH',
                'state.scope = :stateScope AND state.code = :stateCode AND state.value = :stateDeleted'
            )
            ->orderBy('f.id', Criteria::ASC)
            ->setParameter('attributeScope', 'attribute')
            ->setParameter('isAttributeCode', 'is_attribute')
            ->setParameter('isAttributeValue', '1')
            ->setParameter('stateScope', 'extend')
            ->setParameter('stateCode', 'state')
            ->setParameter('stateDeleted', ExtendScope::STATE_DELETE);

        $queryBuilder->andWhere($queryBuilder->expr()->isNull('state.scope'));

        return $queryBuilder;
    }

    /**
     * @return FieldConfigModel[]
     */
    public function getAllAttributes(): array
    {
        return $this->getBaseAttributeQueryBuilder()
            ->getQuery()
            ->getResult();
    }
}
