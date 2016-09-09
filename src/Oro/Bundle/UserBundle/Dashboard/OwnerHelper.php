<?php

namespace Oro\Bundle\UserBundle\Dashboard;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Oro\Component\DoctrineUtils\ORM\QueryUtils;

class OwnerHelper
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var OwnerTreeProviderInterface */
    protected $ownerTreeProvider;

    const CURRENT_USER          = 'current_user';
    const CURRENT_BUSINESS_UNIT = 'current_business_unit';

    /** @var array */
    protected $ownerIds;

    /**
     * @param RegistryInterface          $registry
     * @param TokenStorageInterface      $tokenStorage
     * @param OwnerTreeProviderInterface $ownerTreeProvider
     */
    public function __construct(
        RegistryInterface $registry,
        TokenStorageInterface $tokenStorage,
        OwnerTreeProviderInterface $ownerTreeProvider
    ) {
        $this->registry          = $registry;
        $this->tokenStorage      = $tokenStorage;
        $this->ownerTreeProvider = $ownerTreeProvider;
    }

    /**
     * @param WidgetOptionBag $widgetOptions
     *
     * @return int[] Returns array of user ids, [] if filter is empty or [0] if intersection wasn't found among options
     */
    public function getOwnerIds(WidgetOptionBag $widgetOptions)
    {
        $key = spl_object_hash($widgetOptions);
        if (!isset($this->ownerIds[$key])) {
            $ownerIdsGroups = $this->collectOwnerIdsGroups($widgetOptions);
            $ownerIds = [];
            if ($ownerIdsGroups) {
                if (count($ownerIdsGroups) === 1) {
                    $ownerIds = reset($ownerIdsGroups);
                } else {
                    $ownerIds = call_user_func_array('array_intersect', $ownerIdsGroups);
                }
                if (empty($ownerIds)) {
                    $ownerIds = [0];
                }
            }

            $this->ownerIds[$key] = $ownerIds;
        }

        return $this->ownerIds[$key];
    }

    /**
     * @param WidgetOptionBag $widgetOptions
     *
     * @return array
     */
    protected function collectOwnerIdsGroups(WidgetOptionBag $widgetOptions)
    {
        $ownerIdsGroups = [];

        if ($userIds = $this->replaceCurrentValues($this->getUsersIds($widgetOptions))) {
            $ownerIdsGroups[] = array_unique($userIds);
        }

        if ($businessUnitIds = $this->replaceCurrentValues($this->getBusinessUnitsIds($widgetOptions))) {
            $ownerIdsGroups[] = $this->getUserOwnerIds($businessUnitIds);
        }

        if ($roleIds = $this->getRoleIds($widgetOptions)) {
            $ownerIdsGroups[] = $this->getUserOwnerIdsByRoles($roleIds);
        }

        return $ownerIdsGroups;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public function replaceCurrentValues(array $array)
    {
        $key = array_search(static::CURRENT_USER, $array, true);
        if ($key !== false) {
            $array[$key] = $this->getCurrentUser()->getId();
        }

        $key = array_search(static::CURRENT_BUSINESS_UNIT, $array, true);
        if ($key !== false) {
            $array[$key] = $this->getCurrentUser()->getOwner()->getId();
        }

        return $array;
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }


    /**
     * @param WidgetOptionBag $widgetOptions
     *
     * @return int[]
     */
    protected function getUsersIds(WidgetOptionBag $widgetOptions)
    {
        return $this->getWidgetConfigIds($widgetOptions, 'users');
    }

    /**
     * @param WidgetOptionBag $widgetOptions
     *
     * @return int[]
     */
    protected function getRoleIds(WidgetOptionBag $widgetOptions)
    {
        return $this->getWidgetConfigIds($widgetOptions, 'roles');
    }

    /**
     * @param WidgetOptionBag $widgetOptions
     *
     * @return int[]
     */
    protected function getBusinessUnitsIds(WidgetOptionBag $widgetOptions)
    {
        return $this->getWidgetConfigIds($widgetOptions, 'businessUnits');
    }

    /**
     * @param WidgetOptionBag $widgetOptions
     * @param string          $config
     *
     * @return int[]
     */
    protected function getWidgetConfigIds(WidgetOptionBag $widgetOptions, $config)
    {
        $owners = (array)$widgetOptions->get('owners', []);
        $ids    = array_key_exists($config, $owners) ? (array)$owners[$config] : [];

        return array_filter($ids);
    }

    /**
     * @param int[] $businessUnitIds
     *
     * @return int[]
     */
    protected function getUserOwnerIds(array $businessUnitIds)
    {
        if (!$businessUnitIds) {
            return [];
        }

        return $this->ownerTreeProvider->getTree()->getUsersAssignedToBusinessUnits(
            $this->replaceCurrentValues($businessUnitIds)
        ) ?: [0];
    }

    /**
     * @param int[] $roleIds
     *
     * @return int[]
     */
    protected function getUserOwnerIdsByRoles(array $roleIds)
    {
        if (!$roleIds) {
            return [];
        }

        $qb = $this->registry->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u')
            ->select('DISTINCT(u.id)')
            ->join('u.roles', 'r');
        QueryUtils::applyOptimizedIn($qb, 'r.id', $roleIds);

        $result = array_map('current', $qb->getQuery()->getResult());
        if (empty($result)) {
            $result = [0];
        }

        return $result;
    }
}
