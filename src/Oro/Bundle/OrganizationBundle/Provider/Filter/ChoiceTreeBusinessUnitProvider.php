<?php

namespace Oro\Bundle\OrganizationBundle\Provider\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;

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

        $response = [];

        $qb = $businessUnitRepo->getQueryBuilder();

        $qb->andWhere(
            $qb->expr()->in('businessUnit.id', ':ids')
        );

        $qb->setParameter('ids', $this->getBusinessUnitIds());

        $businessUnits = $this->aclHelper->apply($qb)->getResult();
        /** @var BusinessUnit $businessUnit */
        foreach ($businessUnits as $businessUnit) {
            if ($businessUnit->getOwner()) {
                $name = $businessUnit->getName();
            } else {
                $name = $this->getBusinessUnitName($businessUnit);
            }

            $response[] = [
                'id'       => $businessUnit->getId(),
                'name'     => $name,
                'owner_id' => $businessUnit->getOwner() ? $businessUnit->getOwner()->getId() : null
            ];
        }

        return $response;
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
        $user         = $this->getUser();
        $organization = $user->getOrganization();

        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $userBUIds = $tree->getUserBusinessUnitIds(
            $user->getId(),
            $organization->getId()
        );

        $result = [];
        foreach ($userBUIds as $businessUnitId) {
            $subordinateBUIds = $tree->getSubordinateBusinessUnitIds($businessUnitId);
            $result           = array_merge($subordinateBUIds, $result);
        }

        return array_unique(
            array_merge($userBUIds, $result)
        );
    }
}
