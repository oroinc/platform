<?php
namespace Oro\Bundle\OrganizationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class BusinessUnitRepository extends EntityRepository
{
    /**
     * Finds the first record
     *
     * @return BusinessUnit
     */
    public function getFirst()
    {
        return $this->createQueryBuilder('businessUnit')
            ->select('businessUnit')
            ->orderBy('businessUnit.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleResult();
    }

    /**
     * Build business units tree for user page
     *
     * @param User     $user
     * @param int|null $organizationId
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getBusinessUnitsTree(User $user = null, $organizationId = null)
    {
        $businessUnits = $this->createQueryBuilder('businessUnit')->select(
            array(
                'businessUnit.id',
                'businessUnit.name',
                'IDENTITY(businessUnit.owner) parent',
                'IDENTITY(businessUnit.organization) organization',
            )
        );
        if ($user && $user->getId()) {
            $units = $user->getBusinessUnits()->map(
                function (BusinessUnit $businessUnit) {
                    return $businessUnit->getId();
                }
            );
            $units = $units->toArray();
            if ($units) {
                $businessUnits->addSelect('CASE WHEN businessUnit.id IN (:userUnits) THEN 1 ELSE 0 END as hasUser');
                $businessUnits->setParameter(':userUnits', $units);
            }
        }

        if ($organizationId) {
            $businessUnits->where('businessUnit.organization = :organizationId');
            $businessUnits->setParameter(':organizationId', $organizationId);
        }

        $businessUnits = $businessUnits->getQuery()->getArrayResult();
        $children      = array();
        foreach ($businessUnits as &$businessUnit) {
            $parent              = $businessUnit['parent'] ? : 0;
            $children[$parent][] = & $businessUnit;
        }
        unset($businessUnit);
        foreach ($businessUnits as &$businessUnit) {
            if (isset($children[$businessUnit['id']])) {
                $businessUnit['children'] = $children[$businessUnit['id']];
            }
        }
        unset($businessUnit);
        if (isset($children[0])) {
            $children = $children[0];
        }

        return $children;
    }

    /**
     * Returns business units tree by organization
     * Or returns business units tree for given organization.
     *
     * @param int|null $organizationId
     * @param array $sortOrder array with order parameters. key - organization entity field, value - sort direction
     *
     * @return array
     */
    public function getOrganizationBusinessUnitsTree($organizationId = null, array $sortOrder = [])
    {
        $tree          = [];
        $businessUnits = $this->getBusinessUnitsTree();

        $organizations = $this->_em->getRepository('OroOrganizationBundle:Organization')
            ->getOrganizationsPartialData(
                ['id', 'name', 'enabled'],
                $sortOrder,
                $organizationId ? [$organizationId] : []
            );
        foreach ($organizations as $organizationItem) {
            $tree[$organizationItem['id']] = array_merge($organizationItem, ['children' => []]);
        }

        foreach ($businessUnits as $businessUnit) {
            if ($businessUnit['organization'] == null) {
                continue;
            }
            $tree[$businessUnit['organization']]['children'][] = $businessUnit;
        }

        if ($organizationId && isset($tree[$organizationId])) {
            return $tree[$organizationId]['children'];
        }

        return $tree;
    }

    /**
     * Get business units ids
     *
     * @param int|null $organizationId
     * @return array
     */
    public function getBusinessUnitIds($organizationId = null)
    {
        $result        = [];
        /** @var QueryBuilder $businessUnitsQB */
        $businessUnitsQB = $this->createQueryBuilder('businessUnit');
        $businessUnitsQB->select('businessUnit.id');
        if ($organizationId != null) {
            $businessUnitsQB
                ->where('businessUnit.organization = :organizationId')
                ->setParameter(':organizationId', $organizationId);
        }
        $businessUnits = $businessUnitsQB
            ->getQuery()
            ->getArrayResult();

        foreach ($businessUnits as $buId) {
            $result[] = $buId['id'];
        }

        return $result;
    }

    /**
     * @param array $businessUnits
     * @return mixed
     */
    public function getBusinessUnits(array $businessUnits)
    {
        return $this->createQueryBuilder('businessUnit')
            ->select('businessUnit')
            ->where('businessUnit.id in (:ids)')
            ->setParameter('ids', $businessUnits)
            ->getQuery()
            ->execute();
    }

    /**
     * Get count of business units
     *
     * @return int
     */
    public function getBusinessUnitsCount()
    {
        $qb = $this->createQueryBuilder('businessUnit');
        $qb->select('count(businessUnit.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $field
     * @param string $entity
     * @param string $alias
     *
     * @return array
     */
    public function getGridFilterChoices($field, $entity, $alias = 'bu')
    {
        $options = [];

        $result = $this->_em->createQueryBuilder()
            ->select($alias)
            ->from($entity, $alias)
            ->add('select', $alias . '.' . $field)
            ->distinct($alias . '.' . $field)
            ->getQuery()
            ->getArrayResult();

        foreach ((array)$result as $value) {
            $options[$value[$field]] = current(
                array_reverse(
                    explode('\\', $value[$field])
                )
            );
        }

        return $options;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->createQueryBuilder('businessUnit');
    }
}
