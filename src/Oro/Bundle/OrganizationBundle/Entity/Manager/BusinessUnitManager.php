<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;

class BusinessUnitManager
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @param EntityManager $em
     * @param AclHelper $aclHelper
     */
    public function __construct(EntityManager $em, AclHelper $aclHelper)
    {
        $this->em = $em;
        $this->aclHelper = $aclHelper;
    }

    /**
     * Get Business Units tree
     *
     * @param User $entity
     * @return array
     */
    public function getBusinessUnitsTree(User $entity = null)
    {
        return $this->getBusinessUnitRepo()->getBusinessUnitsTree($entity);
    }

    /**
     * @param User $entity
     * @param array $businessUnits
     */
    public function assignBusinessUnits($entity, array $businessUnits)
    {
        if ($businessUnits) {
            $businessUnits = $this->getBusinessUnitRepo()->getBusinessUnits($businessUnits);
        } else {
            $businessUnits = new ArrayCollection();
        }
        $entity->setBusinessUnits($businessUnits);
    }

    /**
     * @param array $criteria
     * @param array $orderBy
     * @return BusinessUnit
     */
    public function getBusinessUnit(array $criteria = array(), array $orderBy = null)
    {
        return $this->getBusinessUnitRepo()->findOneBy($criteria, $orderBy);
    }

    public function getUserBusinessUnits($permission)
    {
        return $this->aclHelper->apply(
            $this->getBusinessUnitRepo()->createQueryBuilder('bu')->select('bu.id'),
            $permission
        )->getArrayResult();
    }

    /**
     * @return BusinessUnitRepository
     */
    public function getBusinessUnitRepo()
    {
        return $this->em->getRepository('OroOrganizationBundle:BusinessUnit');
    }
}
