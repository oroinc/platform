<?php

namespace Oro\Bundle\OrganizationBundle\Provider\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class ChoiceTreeBusinessUnitProvider
{
    /** @var Registry */
    protected $registry;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param Registry $registry
     * @param SecurityFacade $securityFacade
     * @param AclHelper $aclHelper
     */
    public function __construct(
        Registry $registry,
        SecurityFacade $securityFacade,
        AclHelper $aclHelper
    ) {
        $this->registry = $registry;
        $this->securityFacade = $securityFacade;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param Organization $currentOrganization
     *
     * @return array
     */
    public function getList()
    {
        $businessUnitRepository = $this->getBusinessUnitRepo();
        $response = [];

        $qb = $businessUnitRepository->getQueryBuilder();
        $businessUnits = $this->aclHelper->apply($qb)->getResult();
        /** @var BusinessUnit $businessUnit */
        foreach ($businessUnits as $businessUnit) {
            if ($businessUnit->getOwner()) {
                $name =$businessUnit->getName();
            } else {
                $name = $this->getBusinessUnitName($businessUnit);
            }

            $response[] = [
                'id' => $businessUnit->getId(),
                'name' => $name,
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
        return  $businessUnit->getName();
    }
}
