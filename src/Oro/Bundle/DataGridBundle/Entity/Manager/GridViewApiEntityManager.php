<?php

namespace Oro\Bundle\DataGridBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
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
     */
    public function setDefaultGridView(User $user, ViewInterface $gridView, $default)
    {
        $this->gridViewManager->setDefaultGridView($user, $gridView, $default);

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
        if (!$default) {
            $gridView = new View(GridViewsExtension::DEFAULT_VIEW_ID);
        } else {
            $gridView = $this->gridViewManager->getSystemView($id, $gridName);
            if (!$gridView) {
                $gridView = $this->find($id);
            }
        }

        if ($gridView instanceof ViewInterface) {
            $gridView->setGridName($gridName);
        }

        return $gridView;
    }
}
