<?php

namespace Oro\Bundle\DataGridBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewApiEntityManager extends ApiEntityManager
{
    /** @var GridViewManager */
    protected $gridViewManager;

    /**
     * @param string          $class Entity name
     * @param ObjectManager   $om    Object manager
     * @param GridViewManager $gridViewManager
     */
    public function __construct($class, ObjectManager $om, GridViewManager $gridViewManager)
    {
        parent::__construct($class, $om);

        $this->gridViewManager = $gridViewManager;
    }

    /**
     * @param User     $user
     * @param GridView $gridView
     * @param bool     $default
     */
    public function setDefaultGridView(User $user, GridView $gridView, $default)
    {
        $this->gridViewManager->setDefaultGridView($user, $gridView, $default);

        $this->getObjectManager()->flush();
    }
}
