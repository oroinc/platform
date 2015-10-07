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

        $qb = $businessUnitRepository->getRootBusinessUnits();
        $rootBusinessUnits = $this->aclHelper->apply($qb)->getResult();
        /** @var BusinessUnit $rootBusinessUnit */
        foreach ($rootBusinessUnits as $rootBusinessUnit) {
            if ($rootBusinessUnit->getOwner()) {
                $name = $this->getBusinessUnitName($rootBusinessUnit);
            } else {
                $name = $rootBusinessUnit->getName();
            }

            $response[] = [
                'id' => $rootBusinessUnit->getId(),
                'name' => $name,
                'owner_id' => $rootBusinessUnit->getOwner() ? $rootBusinessUnit->getOwner()->getId() : null
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
     * @param BusinessUnit $rootBusinessUnit
     *
     * @return string
     */
    protected function getBusinessUnitName(BusinessUnit $rootBusinessUnit)
    {
        return  $rootBusinessUnit->getName();
    }
}
