<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;

class AttributeGroupRelationRepository extends EntityRepository
{
    /**
     * @param array $attributeIds
     * @return array
     */
    public function getFamiliesLabelsByAttributeIds(array $attributeIds)
    {
        if (empty($attributeIds)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('attributeGroupRelation')
            ->select('attributeGroupRelation, attributeGroup, attributeFamily')
            ->innerJoin('attributeGroupRelation.attributeGroup', 'attributeGroup')
            ->innerJoin('attributeGroup.attributeFamily', 'attributeFamily');

        $queryBuilder
            ->where($queryBuilder->expr()->in('attributeGroupRelation.entityConfigFieldId', ':ids'))
            ->setParameter('ids', $attributeIds)
            ->orderBy($queryBuilder->expr()->asc('attributeFamily.id'))
            ->getQuery()
            ->getResult();

        $results = $queryBuilder->getQuery()->getResult();

        $families = array_combine($attributeIds, array_fill(0, count($attributeIds), []));
        /** @var AttributeGroupRelation $result */
        foreach ($results as $result) {
            $families[$result->getEntityConfigFieldId()][] = $result->getAttributeGroup()->getAttributeFamily();
        }

        return $families;
    }

    /**
     * @param array $groupIds
     * @return array
     */
    public function getAttributesMapByGroupIds(array $groupIds)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $result = $queryBuilder
            ->select('r.entityConfigFieldId as attribute_id, g.id')
            ->innerJoin('r.attributeGroup', 'g')
            ->where('g.id IN (:ids)')
            ->setParameter('ids', $groupIds)
            ->orderBy($queryBuilder->expr()->asc('r.id'))
            ->getQuery()
            ->getScalarResult();

        $attributeIds = [];
        foreach ($result as $item) {
            $attributeIds[$item['id']][] = (int) $item['attribute_id'];
        }

        return $attributeIds;
    }

    /**
     * @param int $fieldId
     * @return mixed
     */
    public function removeByFieldId($fieldId)
    {
        $qb = $this->createQueryBuilder('attributeGroupRelation');
        $qb
            ->delete()
            ->where($qb->expr()->eq('attributeGroupRelation.entityConfigFieldId', ':id'))
            ->setParameter('id', $fieldId);

        return $qb->getQuery()->execute();
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @return AttributeGroupRelation[]
     */
    public function getAttributeGroupRelationsByFamily(AttributeFamily $attributeFamily)
    {
        $queryBuilder = $this->createQueryBuilder('attributeGroupRelation')
            ->innerJoin('attributeGroupRelation.attributeGroup', 'attributeGroup');

        return $queryBuilder
            ->where($queryBuilder->expr()->eq('attributeGroup.attributeFamily', ':attributeFamily'))
            ->setParameter('attributeFamily', $attributeFamily)
            ->getQuery()
            ->getResult();
    }
}
