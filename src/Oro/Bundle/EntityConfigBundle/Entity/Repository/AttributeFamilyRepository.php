<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class AttributeFamilyRepository extends EntityRepository
{
    /**
     * @param integer $attributeId
     * @return AttributeFamily[]
     */
    public function getFamiliesByAttributeId($attributeId)
    {
        $queryBuilder = $this->createQueryBuilder('f');

        return $queryBuilder
            ->innerJoin('f.attributeGroups', 'g')
            ->innerJoin('g.attributeRelations', 'r')
            ->where('r.entityConfigFieldId = :id')
            ->setParameter('id', $attributeId)
            ->orderBy($queryBuilder->expr()->asc('f.id'))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array
     * @return array ['<attributeId>' => [<'familyId'>, <'familyId'>], ...]
     */
    public function getFamilyIdsForAttributes(array $attributes)
    {
        $qb = $this->createQueryBuilder('f');

        $attributes = array_map(
            function ($attribute) {
                return $attribute instanceof FieldConfigModel ? $attribute->getId() : $attribute;
            },
            $attributes
        );

        $result = $qb
            ->resetDQLPart('select')
            ->select('f.id AS familyId, r.entityConfigFieldId AS attributeId')
            ->innerJoin('f.attributeGroups', 'g')
            ->innerJoin('g.attributeRelations', 'r')
            ->where('r.entityConfigFieldId IN (:ids)')
            ->setParameter('ids', $attributes)
            ->getQuery()
            ->getArrayResult();

        $attributeIds = [];
        foreach ($result as $item) {
            $attributeIds[(int) $item['attributeId']][] = (int) $item['familyId'];
        }

        return $attributeIds;
    }

    /**
     * @param string $entityClass
     * @return int
     */
    public function countFamiliesByEntityClass($entityClass)
    {
        $queryBuilder = $this->createQueryBuilder('family');

        return (int) $queryBuilder
            ->select($queryBuilder->expr()->count('family.id'))
            ->andWhere($queryBuilder->expr()->eq('family.entityClass', ':entityClass'))
            ->setParameter('entityClass', $entityClass)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
