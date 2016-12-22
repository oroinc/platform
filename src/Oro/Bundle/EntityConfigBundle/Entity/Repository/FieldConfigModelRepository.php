<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

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
        $attributes = $this->getBaseAttrubuteQueryBuilderByIds($ids)
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
        $attributes = $this->getBaseAttrubuteQueryBuilderByIds($ids)
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
                's.scope = :attributeScope AND s.code = :isSystemCode AND s.value = :isSystemValue'
            )
            ->setParameter('isSystemCode', 'is_system')
            ->setParameter('isSystemValue', $isSystem ? '1' : '0')
            ->getQuery()
            ->getResult();

        return $attributes;
    }

    /**
     * @param array $ids
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getBaseAttrubuteQueryBuilderByIds(array $ids)
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
