<?php

namespace Oro\Bundle\DataGridBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Entity\GridView;
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
     * @param GridView|ViewInterface $gridView
     * @param bool $default
     * @param $gridName
     */
    public function setDefaultGridView(User $user, ViewInterface $gridView, $default, $gridName)
    {
        $this->gridViewManager->setDefaultGridView($user, $gridView, $default, $gridName);

        $this->getObjectManager()->flush();
    }

    /**
     * Get GridView or System View by id
     * @param $id
     * @param null $gridName
     * @return null|object
     */
    public function getView($id, $gridName = null)
    {
        if (!$gridName) {
            $gridView = $this->find($id);
        } else {
            $gridView = $this->gridViewManager->getSystemView($id, $gridName);
            if (!$gridView) {
                $gridView = $this->find($id);
            }
        }

        return $gridView;
    }
}
