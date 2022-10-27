<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Doctrine repository for AttributeFamily entity
 */
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
     * @param array $attributes
     * @return array ['<attributeId>' => [<'familyId'>, <'familyId'>], ...]
     */
    public function getFamilyIdsForAttributes(array $attributes): array
    {
        return $this->getFamilyIds($this->getFamilyIdsForAttributesQb($attributes));
    }

    public function getFamilyIdsForAttributesQb(array $attributes): QueryBuilder
    {
        return $this->createQueryBuilder('f')
            ->resetDQLPart('select')
            ->select('f.id AS familyId, r.entityConfigFieldId AS attributeId')
            ->innerJoin('f.attributeGroups', 'g')
            ->innerJoin('g.attributeRelations', 'r')
            ->where('r.entityConfigFieldId IN (:ids)')
            ->setParameter('ids', $this->prepareAttributes($attributes));
    }

    /**
     * @param array $attributes
     * @param OrganizationInterface $organization
     * @return array ['<attributeId>' => [<'familyId'>, <'familyId'>], ...]
     */
    public function getFamilyIdsForAttributesByOrganization(
        array $attributes,
        OrganizationInterface $organization
    ): array {
        $qb = $this->getFamilyIdsForAttributesQb($attributes);
        $qb->andWhere($qb->expr()->eq('f.owner', ':organization'))
            ->setParameter('organization', $organization);

        return $this->getFamilyIds($qb);
    }

    /**
     * @param string $entityClass
     * @return int
     */
    public function countFamiliesByEntityClass($entityClass)
    {
        $queryBuilder = $this->createQueryBuilder('family');

        return (int)$queryBuilder
            ->select($queryBuilder->expr()->count('family.id'))
            ->andWhere($queryBuilder->expr()->eq('family.entityClass', ':entityClass'))
            ->setParameter('entityClass', $entityClass)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array $attributes
     * @return array|int[]
     */
    private function prepareAttributes(array $attributes): array
    {
        return array_map(
            static function ($attribute) {
                return $attribute instanceof FieldConfigModel ? $attribute->getId() : $attribute;
            },
            $attributes
        );
    }

    private function getFamilyIds(QueryBuilder $qb): array
    {
        $result = $qb->getQuery()->getArrayResult();

        $attributeIds = [];
        foreach ($result as $item) {
            $attributeIds[(int)$item['attributeId']][] = (int)$item['familyId'];
        }

        return $attributeIds;
    }

    public function getFamilyByCode(string $attributeFamilyCode, AclHelper $aclHelper): ?AttributeFamily
    {
        $qb = $this->createQueryBuilder('f');
        $qb->andWhere($qb->expr()->eq('f.code', ':familyCode'))
            ->setParameter('familyCode', $attributeFamilyCode)
            ->setMaxResults(1);

        return $aclHelper->apply($qb)->getOneOrNullResult();
    }
}
