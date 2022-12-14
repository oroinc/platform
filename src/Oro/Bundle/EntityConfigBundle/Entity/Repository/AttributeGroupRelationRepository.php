<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * ORM repository for AttributeGroupRelation entity.
 */
class AttributeGroupRelationRepository extends EntityRepository
{
    /**
     * @param array $attributeIds
     * @param AclHelper $aclHelper
     * @return array
     */
    public function getFamiliesLabelsByAttributeIdsWithAcl(array $attributeIds, AclHelper $aclHelper)
    {
        if (empty($attributeIds)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('attributeGroupRelation');
        $queryBuilder
            ->select(
                'attributeGroupRelation.entityConfigFieldId as attribute_id',
                'attributeFamily.id'
            )
            ->innerJoin('attributeGroupRelation.attributeGroup', 'attributeGroup')
            ->innerJoin('attributeGroup.attributeFamily', 'attributeFamily')
            ->where($queryBuilder->expr()->in('attributeGroupRelation.entityConfigFieldId', ':ids'))
            ->setParameter('ids', $attributeIds)
            ->orderBy($queryBuilder->expr()->asc('attributeFamily.id'));

        $attributesToFamilies = $aclHelper->apply($queryBuilder->getQuery())->getResult();

        $attributeFamilyData = [];
        foreach ($attributesToFamilies as $attributeToFamily) {
            $attributeFamilyData[$attributeToFamily['id']]['attributes'][] = $attributeToFamily['attribute_id'];
        }

        foreach ($this->getLabelsForFamilies(array_keys($attributeFamilyData)) as $label) {
            $attributeFamilyData[$label['family_id']]['labels'][] = $label[0];
        }

        $result = [];
        foreach ($attributeFamilyData as $familyId => $attributeFamilyRow) {
            $labels = new ArrayCollection($attributeFamilyRow['labels']);
            foreach ($attributeFamilyRow['attributes'] as $attributeId) {
                $result[$attributeId][] = ['id' => $familyId, 'labels' => $labels];
            }
        }

        $attributeIdsWithFamilies = array_keys($result);
        foreach (array_diff($attributeIds, $attributeIdsWithFamilies) as $attributeIdWithoutFamily) {
            $result[$attributeIdWithoutFamily] = [];
        }

        return $result;
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

    public function getAttributesIdsByFamilies(array $families = []): array
    {
        $queryBuilder = $this->createQueryBuilder('attributeGroupRelation');

        $queryBuilder
            ->select('DISTINCT(attributeGroupRelation.entityConfigFieldId)')
            ->innerJoin('attributeGroupRelation.attributeGroup', 'attributeGroup');
        if ($families) {
            $queryBuilder
                ->where($queryBuilder->expr()->in('attributeGroup.attributeFamily', ':families'))
                ->setParameter('families', $families);
        } else {
            $queryBuilder->where($queryBuilder->expr()->isNotNull('attributeGroup.attributeFamily'));
        }


        return $queryBuilder->getQuery()->getSingleColumnResult();
    }

    /**
     * @param array|int[] $familyIds
     * @return array
     */
    private function getLabelsForFamilies(array $familyIds): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(LocalizedFallbackValue::class, 'lfv')
            ->select('lfv', 'af.id as family_id')
            ->join(AttributeFamily::class, 'af', Join::WITH, $qb->expr()->isMemberOf('lfv', 'af.labels'))
            ->where($qb->expr()->in('af.id', ':attributeFamilyIds'))
            ->setParameter('attributeFamilyIds', $familyIds);

        return $qb->getQuery()->getResult();
    }
}
