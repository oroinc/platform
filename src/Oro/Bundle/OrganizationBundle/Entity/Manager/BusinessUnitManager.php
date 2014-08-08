<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;

class BusinessUnitManager
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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
     * Get business units ids
     *
     * @return array
     */
    public function getBusinessUnitIds()
    {
        return $this->getBusinessUnitRepo()->getBusinessUnitIds();
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

    /**
     * Checks if user can be set as owner by given user
     *
     * @param User              $currentUser
     * @param User              $newUser
     * @param string            $accessLevel
     * @param OwnerTreeProvider $treeProvider
     * @param Organization      $organization
     * @return bool
     */
    public function canUserBeSetAsOwner(
        User $currentUser,
        User $newUser,
        $accessLevel,
        OwnerTreeProvider $treeProvider,
        Organization $organization
    ) {
        $userId = $newUser->getId();
        if ($accessLevel == AccessLevel::SYSTEM_LEVEL) {
            return true;
        } elseif ($accessLevel == AccessLevel::BASIC_LEVEL && $userId == $currentUser->getId()) {
            return true;
        } elseif ($accessLevel == AccessLevel::GLOBAL_LEVEL && $newUser->getOrganizations()->contains($organization)) {
            return true;
        } else {
            $resultBuIds = [];
            if ($accessLevel == AccessLevel::LOCAL_LEVEL) {
                $resultBuIds = $treeProvider->getTree()->getUserBusinessUnitIds(
                    $currentUser->getId(),
                    $organization->getId()
                );
            } elseif ($accessLevel == AccessLevel::DEEP_LEVEL) {
                $resultBuIds = $treeProvider->getTree()->getUserSubordinateBusinessUnitIds(
                    $currentUser->getId(),
                    $organization->getId()
                );
            }

            if (!empty($resultBuIds)) {
                $assignedUser = $this->getUserRepo()->find($userId);
                return (in_array($assignedUser->getOwner()->getId(), $resultBuIds));
            }
        }

        return false;
    }

    /**
     * @return BusinessUnitRepository
     */
    public function getBusinessUnitRepo()
    {
        return $this->em->getRepository('OroOrganizationBundle:BusinessUnit');
    }

    /**
     * @return EntityRepository
     */
    public function getUserRepo()
    {
        return $this->em->getRepository('OroUserBundle:User');
    }
}
