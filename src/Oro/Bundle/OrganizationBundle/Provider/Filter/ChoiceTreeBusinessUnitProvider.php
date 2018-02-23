<?php

namespace Oro\Bundle\OrganizationBundle\Provider\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Owner\ChainOwnerTreeProvider;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class ChoiceTreeBusinessUnitProvider
{
    /** @var Registry */
    protected $registry;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var ChainOwnerTreeProvider */
    protected $treeProvider;

    /**
     * @param Registry                $registry
     * @param TokenAccessorInterface  $tokenAccessor
     * @param AclHelper               $aclHelper
     * @param ChainOwnerTreeProvider  $treeProvider
     */
    public function __construct(
        Registry $registry,
        TokenAccessorInterface $tokenAccessor,
        AclHelper $aclHelper,
        ChainOwnerTreeProvider $treeProvider
    ) {
        $this->registry = $registry;
        $this->tokenAccessor = $tokenAccessor;
        $this->aclHelper = $aclHelper;
        $this->treeProvider = $treeProvider;
    }

    /**
     * @return array
     */
    public function getList()
    {
        $businessUnitRepo = $this->getBusinessUnitRepo();

        $qb = $businessUnitRepo->getQueryBuilder();
        $qb
            ->select('businessUnit.id')
            ->addSelect('o.id AS owner_id')
            ->leftJoin('businessUnit.owner', 'o')
            ->orderBy('businessUnit.id', 'ASC');
        $this->addBusinessUnitName($qb);
        QueryBuilderUtil::applyOptimizedIn($qb, 'businessUnit.id', $this->getBusinessUnitIds());

        return $this->aclHelper->apply($qb)->getArrayResult();
    }

    /**
     * @return bool
     */
    public function shouldBeLazy()
    {
        return count($this->getBusinessUnitIds()) >= 500;
    }

    /**
     * @return BusinessUnitRepository
     */
    protected function getBusinessUnitRepo()
    {
        return $this->registry->getRepository('OroOrganizationBundle:BusinessUnit');
    }

    /**
     * @param BusinessUnit $businessUnit
     *
     * @return string
     */
    protected function getBusinessUnitName(BusinessUnit $businessUnit)
    {
        return $businessUnit->getName();
    }

    /**
     * @param QueryBuilder $qb
     */
    protected function addBusinessUnitName(QueryBuilder $qb)
    {
        $qb->addSelect('businessUnit.name');
    }

    /**
     * @return array
     */
    protected function getBusinessUnitIds()
    {
        $tree = $this->treeProvider->getTree();
        $user = $this->tokenAccessor->getUser();
        $result = [];

        $organizations = $user->getOrganizations();

        foreach ($organizations as $organization) {
            $subBUIds = $tree->getUserSubordinateBusinessUnitIds($user->getId(), $organization->getId());
            $result   = array_merge($result, $subBUIds);
        }

        return array_unique($result);
    }
}
