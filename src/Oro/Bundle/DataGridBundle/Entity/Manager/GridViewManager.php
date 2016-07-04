<?php

namespace Oro\Bundle\DataGridBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\GridViewUser;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;

use Oro\Component\PhpUtils\ArrayUtil;

class GridViewManager
{
    const DEFAULT_VIEW_KEY = 'default_view';
    const SYSTEM_VIEWS_KEY = 'system_views';
    const ALL_VIEWS_KEY    = 'all_views';

    /** @var AclHelper */
    protected $aclHelper;

    /** @var Registry */
    protected $registry;

    /** @var  Manager */
    protected $gridManager;

    protected $cacheData;

    /**
     * @param AclHelper $aclHelper
     * @param Registry $registry
     * @param Manager $gridManager
     */
    public function __construct(AclHelper $aclHelper, Registry $registry, Manager $gridManager)
    {
        $this->aclHelper = $aclHelper;
        $this->registry = $registry;
        $this->gridManager = $gridManager;
    }

    /**
     * @param User $user
     * @param ViewInterface $gridView
     */
    public function setDefaultGridView(User $user, ViewInterface $gridView)
    {
        $isGridViewDefault = $this->isViewDefault($gridView, $user);
        // Checks if default grid view changed
        if (!$isGridViewDefault) {
            /** @var GridViewRepository $repository */
            $gridName = $gridView->getGridName();
            $om = $this->registry->getManagerForClass('OroDataGridBundle:GridViewUser');
            $repository = $om->getRepository('OroDataGridBundle:GridViewUser');
            $userViews = $repository->findDefaultGridViews($this->aclHelper, $user, $gridName, false);
            foreach ($userViews as $userView) {
                $om->remove($userView);
            }

            $userView = new GridViewUser();
            $userView->setAlias($gridView->getName());
            $userView->setUser($user);
            $userView->setGridName($gridName);
            if ($gridView instanceof GridView) {
                $userView->setGridView($gridView);
            }
            $om->persist($userView);
        }
    }

    /**
     * @param ViewInterface $view
     * @param User $user
     * @return bool
     */
    protected function isViewDefault(ViewInterface $view, User $user)
    {
        if ($view instanceof GridView) {
            $isDefault = $view->getUsers()->contains($user);
        } else {
            $defaultViews = $this->registry
                ->getManagerForClass('OroDataGridBundle:GridViewUser')
                ->getRepository('OroDataGridBundle:GridViewUser')
                ->findBy(['user' => $user, 'alias' => $view->getName(), 'gridName' => $view->getGridName()]);
            $isDefault = count($defaultViews) ? true : false;
        }

        return $isDefault;
    }

    /**
     * @param $id
     * @param $gridName
     * @return null|View
     */
    public function getSystemView($id, $gridName)
    {
        $gridViews = $this->getSystemViews($gridName);
        foreach ($gridViews as $gridView) {
            if ($gridView->getName() == $id) {
                return $gridView;
            }
        }

        return null;
    }

    /**
     * Get all system views by gridName
     *
     * @param $gridName
     * @return array
     */
    public function getSystemViews($gridName)
    {
        if (!isset($this->cacheData[self::SYSTEM_VIEWS_KEY])) {
            $config = $this->gridManager->getConfigurationForGrid($gridName);
            $list = $config->offsetGetOr(GridViewsExtension::VIEWS_LIST_KEY, false);
            $gridViews[] = new View(GridViewsExtension::DEFAULT_VIEW_ID);
            if ($list) {
                $gridViews = array_merge($gridViews, $list->getList()->getValues());
            }
            $this->cacheData[self::SYSTEM_VIEWS_KEY] = $gridViews;
        }

        return $this->cacheData[self::SYSTEM_VIEWS_KEY];
    }

    /**
     * @param $user
     * @param $gridName
     * @return array
     */
    public function getAllGridViews($user, $gridName)
    {
        if (!isset($this->cacheData[self::ALL_VIEWS_KEY])) {
            $systemViews = $this->getSystemViews($gridName);
            $gridViews = $this->registry
                ->getRepository('OroDataGridBundle:GridView')
                ->findGridViews($this->aclHelper, $user, $gridName);
            $this->cacheData[self::ALL_VIEWS_KEY] = [
                'system' => $systemViews,
                'user' => $gridViews
            ];
        }

        return $this->cacheData[self::ALL_VIEWS_KEY];
    }

    /**
     * Get default view from all views (user made, system, etc)
     *
     * @param $user
     * @param $gridName
     * @return mixed
     */
    public function getDefaultView($user, $gridName)
    {
        if (!isset($this->cacheData[self::DEFAULT_VIEW_KEY])) {
            $gridViewRepository = $this->registry->getRepository('OroDataGridBundle:GridViewUser');
            $default = $gridViewRepository->findDefaultGridView($this->aclHelper, $user, $gridName, false);
            $systemViews = $this->getSystemViews($gridName);
            if (!$default) {
                $defaultView = ArrayUtil::find(
                    function ($systemView) {
                        return $systemView->isDefault();
                    },
                    $systemViews
                );
            } elseif ($default->getGridView()) {
                $defaultView = $this->registry
                    ->getRepository('OroDataGridBundle:GridView')
                    ->find($default->getGridView());
            } else {
                $defaultView = ArrayUtil::find(
                    function ($systemView) use ($default) {
                        return $default->getAlias() == $systemView->getName();
                    },
                    $systemViews
                );
            }
            $this->cacheData[self::DEFAULT_VIEW_KEY] = $defaultView;
        }

        return $this->cacheData[self::DEFAULT_VIEW_KEY];
    }

    /**
     * @param $id
     * @param $default (if $default=0 all views will unset as default and __all__ view will be set
     * @param $gridName
     * @return null|View
     */
    public function getView($id, $default, $gridName)
    {
        if (!$default) {
            $gridView = new View(GridViewsExtension::DEFAULT_VIEW_ID);
        } else {
            $gridView = $this->getSystemView($id, $gridName);
            if (!$gridView) {
                $gridView = $this->registry
                    ->getRepository('OroDataGridBundle:GridView')
                    ->find($id);
            }
        }

        if ($gridView instanceof ViewInterface) {
            $gridView->setGridName($gridName);
        }

        return $gridView;
    }
}
