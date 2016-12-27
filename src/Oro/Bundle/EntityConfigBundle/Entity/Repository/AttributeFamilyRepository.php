<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;

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
