<?php

namespace Oro\Bundle\DataGridBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface;
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
     * @param User $user
     * @param ViewInterface $gridView
     */
    public function setDefaultGridView(User $user, ViewInterface $gridView)
    {
        $this->gridViewManager->setDefaultGridView($user, $gridView);

        $this->getObjectManager()->flush();
    }

    /**
     * Get GridView or System View by id
     * @param $id
     * @param string $gridName
     * @param $default
     * @return null|object
     */
    public function getView($id, $default, $gridName)
    {
        return $this->gridViewManager->getView($id, $default, $gridName);
    }
}
