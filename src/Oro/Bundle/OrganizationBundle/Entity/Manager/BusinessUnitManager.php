<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;

class BusinessUnitManager
{
    /**
     * @var \Doctrine\ORM\EntityManager
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

    /**
     * Checks if current user can set assigned user as owner on given access level
     *
     * @param User $assignedUser
     * @param User $currentUser
     * @param $accessLevel
     * @param OwnerTreeProvider $treeProvider
     * @return bool
     */
    public function isUserIsCorrectOwner(
        $assignedUser,
        User $currentUser,
        $accessLevel,
        OwnerTreeProvider $treeProvider
    ) {
        if ($accessLevel == AccessLevel::BASIC_LEVEL && $assignedUser == $currentUser->getId()) {
            return true;
        } elseif ($accessLevel == AccessLevel::SYSTEM_LEVEL) {
            return true;
        } else {
            $resultBuIds = [];
            if ($accessLevel == AccessLevel::LOCAL_LEVEL) {
                $resultBuIds = $treeProvider->getTree()->getUserBusinessUnitIds($currentUser->getId());
            } elseif ($accessLevel == AccessLevel::DEEP_LEVEL) {
                $buIds = $treeProvider->getTree()->getUserBusinessUnitIds($currentUser->getId());
                $resultBuIds = array_merge($buIds, []);
                foreach ($buIds as $buId) {
                    $diff = array_diff(
                        $treeProvider->getTree()->getSubordinateBusinessUnitIds($buId),
                        $resultBuIds
                    );
                    if (!empty($diff)) {
                        $resultBuIds = array_merge($resultBuIds, $diff);
                    }
                }
            } elseif ($accessLevel == AccessLevel::GLOBAL_LEVEL) {
                foreach ($treeProvider->getTree()->getUserOrganizationIds($currentUser->getId()) as $orgId) {
                    $buIds = $treeProvider->getTree()->getOrganizationBusinessUnitIds($orgId);
                    if (!empty($buIds)) {
                        $resultBuIds = array_merge($resultBuIds, $buIds);
                    }
                }
            }

            if (!empty($resultBuIds)) {
                $assignedUser = $this->getUserRepo()->find($assignedUser);
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
