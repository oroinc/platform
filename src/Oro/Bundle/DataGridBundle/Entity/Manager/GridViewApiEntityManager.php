<?php

namespace Oro\Bundle\DataGridBundle\Entity\Manager;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewApiEntityManager extends ApiEntityManager
{
    /**
     * @param User     $user
     * @param GridView $gridView
     * @param bool     $default
     */
    public function setDefaultGridView(User $user, GridView $gridView, $default)
    {
        /** @var GridViewRepository $repository */
        $repository = $this->getRepository();
        $gridViews = $repository->findDefaultGridViews($user, $gridView);

        foreach ($gridViews as $view) {
            $view->removeUser($user);
        }
        if ($default) {
            $gridView->addUser($user);
        }

        $this->getObjectManager()->flush();
    }
}
