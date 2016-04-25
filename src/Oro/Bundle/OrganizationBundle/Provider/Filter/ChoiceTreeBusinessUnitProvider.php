<?php

namespace Oro\Bundle\OrganizationBundle\Provider\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Owner\ChainOwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\UserBundle\Entity\User;

class ChoiceTreeBusinessUnitProvider
{
    /** @var Registry */
    protected $registry;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ChainOwnerTreeProvider */
    protected $treeProvider;

    /**
     * @param Registry               $registry
     * @param SecurityFacade         $securityFacade
     * @param AclHelper              $aclHelper
     * @param ChainOwnerTreeProvider $treeProvider
     */
    public function __construct(
        Registry $registry,
        SecurityFacade $securityFacade,
        AclHelper $aclHelper,
        ChainOwnerTreeProvider $treeProvider
    ) {
        $this->registry       = $registry;
        $this->securityFacade = $securityFacade;
        $this->aclHelper      = $aclHelper;
        $this->treeProvider   = $treeProvider;
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
        QueryUtils::applyOptimizedIn($qb, 'businessUnit.id', $this->getBusinessUnitIds());

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
     * @return User
     */
    protected function getUser()
    {
        return $this->securityFacade->getToken()->getUser();
    }

    /**
     * @return array
     */
    protected function getBusinessUnitIds()
    {
        /** @var OwnerTree $tree */
        $tree   = $this->treeProvider->getTree();
        $user   = $this->getUser();
        $result = [];

        $organizations = $user->getOrganizations();

        foreach ($organizations as $organization) {
            $subBUIds = $tree->getUserSubordinateBusinessUnitIds($user->getId(), $organization->getId());
            $result   = array_merge($result, $subBUIds);
        }

        return array_unique($result);
    }
}
