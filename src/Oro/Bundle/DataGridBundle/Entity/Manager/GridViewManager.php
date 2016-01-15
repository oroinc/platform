<?php

namespace Oro\Bundle\DataGridBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewManager
{
    /** @var AclHelper */
    protected $aclHelper;

    /** @var Registry */
    protected $registry;

    /**
     * @param AclHelper $aclHelper
     * @param Registry  $registry
     */
    public function __construct(AclHelper $aclHelper, Registry $registry)
    {
        $this->aclHelper = $aclHelper;
        $this->registry  = $registry;
    }

    /**
     * @param User     $user
     * @param GridView $gridView
     * @param bool     $default
     */
    public function setDefaultGridView(User $user, GridView $gridView, $default)
    {
        $isGridViewDefault = $gridView->getUsers()->contains($user);
        // Checks if default grid view changed
        if ($isGridViewDefault !== $default) {
            $om = $this->registry->getManagerForClass('OroDataGridBundle:GridView');
            /** @var GridViewRepository $repository */
            $repository = $om->getRepository('OroDataGridBundle:GridView');
            $gridViews  = $repository->findDefaultGridViews($this->aclHelper, $user, $gridView, false);
            foreach ($gridViews as $view) {
                $view->removeUser($user);
            }

            if ($default) {
                $gridView->addUser($user);
            }
        }
    }
}
