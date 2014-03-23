<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
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
     * Get list of business units with tree levels
     *
     * @return ArrayCollection
     */
    public function getBusinessUnitTreeWithLevels()
    {
        $result = new ArrayCollection();
        $businessUnits = $this->getBusinessUnitRepo()->findBy(['owner' => null]);
        foreach ($businessUnits as $businessUnit) {
            $this->processTree($result, $businessUnit);
        }

        return $result;
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
     * @param User $currentUser
     * @param int $userId
     * @param $accessLevel
     * @param OwnerTreeProvider $treeProvider
     * @return bool
     */
    public function canUserBeSetAsOwner(
        User $currentUser,
        $userId,
        $accessLevel,
        OwnerTreeProvider $treeProvider
    ) {
        if ($accessLevel == AccessLevel::SYSTEM_LEVEL) {
            return true;
        } elseif ($accessLevel == AccessLevel::BASIC_LEVEL && $userId == $currentUser->getId()) {
            return true;
        } else {
            $resultBuIds = [];
            if ($accessLevel == AccessLevel::LOCAL_LEVEL) {
                $resultBuIds = $treeProvider->getTree()->getUserBusinessUnitIds($currentUser->getId());
            } elseif ($accessLevel == AccessLevel::DEEP_LEVEL) {
                $resultBuIds = $treeProvider->getTree()->getUserSubordinateBusinessUnitIds($currentUser->getId());
            } elseif ($accessLevel == AccessLevel::GLOBAL_LEVEL) {
                $resultBuIds = $treeProvider->getTree()->getBusinessUnitsIdByUserOrganizations($currentUser->getId());
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

    /**
     * @param ArrayCollection $result
     * @param BusinessUnit    $businessUnit
     * @param int             $level
     */
    protected function processTree(ArrayCollection $result, BusinessUnit $businessUnit, $level = 0)
    {
        $businessUnit->setLevel($level);
        $result->add($businessUnit);
        $children = $businessUnit->getChildren();
        if ($children->count()) {
            foreach ($children as $child) {
                $this->processTree($result, $child, $level + 1);
            }
        }
    }
}
