<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

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
        $attributes = $this->getBaseAttributeQueryBuilderByClass($className)
            ->innerJoin(
                'f.indexedValues',
                's',
                'WITH',
                's.scope = :extendScope AND s.code = :stateCode AND s.value = :stateValue'
            )
            ->setParameter('stateCode', 'state')
            ->setParameter('extendScope', 'extend')
            ->setParameter('stateValue', ExtendScope::STATE_ACTIVE)
            ->getQuery()
            ->getResult();

        return $attributes;
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

    /**
     * @param array $ids
     * @return \Doctrine\ORM\QueryBuilder
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
     * @return \Doctrine\ORM\QueryBuilder
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
     * @return \Doctrine\ORM\QueryBuilder
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
}
