<?php

namespace Oro\Bundle\UserBundle\Dashboard;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

class OwnerHelper
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var array */
    protected $ownerIds;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
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

            $this->ownerIds[$key] = $array;
        }

        return $this->ownerIds[$key];
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

        array_walk(
            $ids,
            function (&$val) {
                $val = (int)$val;
            }
        );

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

        $qb = $this->registry->getRepository('OroUserBundle:User')
            ->createQueryBuilder('u')
            ->select('DISTINCT(u.id)')
            ->join('u.businessUnits', 'bu');
        QueryUtils::applyOptimizedIn($qb, 'bu.id', $businessUnitIds);

        return array_map('current', $qb->getQuery()->getResult());
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

        return array_map('current', $qb->getQuery()->getResult());
    }
}
