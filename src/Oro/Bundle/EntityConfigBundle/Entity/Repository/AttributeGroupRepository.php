<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;

/**
 * Repository for querying and retrieving attribute group entities.
 *
 * This repository provides specialized query methods for accessing attribute groups, including retrieval
 * by identifiers and fetching groups with their associated attribute relations for a specific attribute family.
 */
class AttributeGroupRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return AttributeGroup[]
     */
    public function getGroupsByIds(array $ids)
    {
        $qb = $this->createQueryBuilder('g', 'g.id');

        return $qb
            ->andWhere($qb->expr()->in('g.id', ':ids'))
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @return AttributeGroup[]
     */
    public function getGroupsWithAttributeRelations(AttributeFamily $attributeFamily)
    {
        $qb = $this->createQueryBuilder('attributeGroup');

        return $qb
            ->leftJoin('attributeGroup.attributeRelations', 'attributeRelations')
            ->andWhere($qb->expr()->eq('attributeGroup.attributeFamily', ':attributeFamily'))
            ->setParameter('attributeFamily', $attributeFamily)
            ->orderBy($qb->expr()->asc('attributeGroup.id'))
            ->getQuery()
            ->getResult();
    }
}
