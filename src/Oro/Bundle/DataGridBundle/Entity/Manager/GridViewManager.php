<?php

namespace Oro\Bundle\DataGridBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\GridViewUser;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewManager
{
    /** @var AclHelper */
    protected $aclHelper;

    /** @var Registry */
    protected $registry;

    /** @var  DataGridExtension */
    protected $gridManager;

    /**
     * @param AclHelper $aclHelper
     * @param Registry $registry
     * @param DataGridExtension $extension
     */
    public function __construct(AclHelper $aclHelper, Registry $registry, Manager $gridManager)
    {
        $this->aclHelper = $aclHelper;
        $this->registry  = $registry;
        $this->gridManager = $gridManager;
    }

    /**
     * @param User     $user
     * @param ViewInterface $gridView
     * @param bool     $default
     * @param string   $gridName
     */
    public function setDefaultGridView(User $user, ViewInterface $gridView, $default, $gridName)
    {
        $isGridViewDefault = $this->isViewDefault($gridView, $user);
        // Checks if default grid view changed
        if ($isGridViewDefault !== $default) {
            /** @var GridViewRepository $repository */
            $gridName = $gridName ? $gridName : $gridView->getGridName();
            $om = $this->registry->getManagerForClass('OroDataGridBundle:GridViewUser');
            $repository = $om->getRepository('OroDataGridBundle:GridViewUser');
            $userViews = $repository->findDefaultGridViews($this->aclHelper, $user, $gridName, false);
            foreach ($userViews as $userView) {
                $om->remove($userView);
            }

            if ($default) {
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
                ->findBy(['user' => $user, 'alias' => $view->getName()]);
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
        $config = $this->gridManager->getConfigurationForGrid($gridName);
        $list = $config->offsetGetOr('views_list', false);
        $gridViews = $list->getList()->getValues();
        foreach ($gridViews as $gridView) {
            if ($gridView->getName() == $id) {
                return $gridView;
            }
        }

        return null;
    }
}
