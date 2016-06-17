<?php

namespace Oro\Bundle\UserBundle\Dashboard;

use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

class OwnerHelper
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    const CURRENT_USER          = 'current_user';
    const CURRENT_BUSINESS_UNIT = 'current_business_unit';

    /** @var array */
    protected $ownerIds;

    /**
     * @param RegistryInterface     $registry
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(RegistryInterface $registry, TokenStorageInterface $tokenStorage)
    {
        $this->registry     = $registry;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param WidgetOptionBag $widgetOptions
     *
     * @return int[]
     */
    public function getOwnerIds(WidgetOptionBag $widgetOptions)
    {
        $key = spl_object_hash($widgetOptions);
        if (!isset($this->ownerIds[$key])) {
            $businessUnitIds = $this->getBusinessUnitsIds($widgetOptions);

            $array = array_unique(
                array_merge($this->getUserOwnerIds($businessUnitIds), $this->getUsersIds($widgetOptions))
            );

            $roleIds = $this->getRoleIds($widgetOptions);
            $array   = array_unique(array_merge($this->getUserOwnerIdsByRoles($roleIds), $array));

            $array = $this->replaceCurrentValues($array);

            $this->ownerIds[$key] = $array;
        }

        return $this->ownerIds[$key];
    }

    /**
     * @param array $array
     *
     * @return array
     */
    public function replaceCurrentValues(array $array)
    {
        $key = array_search(static::CURRENT_USER, $array);
        if ($key !== false) {
            $array[$key] = $this->getCurrentUser()->getId();
        }

        $key = array_search(static::CURRENT_BUSINESS_UNIT, $array);
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

        $businessUnitIds = $this->replaceCurrentValues($businessUnitIds);

        $qb = $this->registry->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u')
            ->select('DISTINCT(u.id)')
            ->join('u.businessUnits', 'bu');
        QueryUtils::applyOptimizedIn($qb, 'bu.id', $businessUnitIds);

        $result = array_map('current', $qb->getQuery()->getResult());

        if (empty($result)) {
            $result = [0];
        }

        return $result;
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
