<?php

namespace Oro\Bundle\DataGridBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewApiEntityManager extends ApiEntityManager
{
    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param string        $class Entity name
     * @param ObjectManager $om    Object manager
     * @param AclHelper     $aclHelper
     */
    public function __construct($class, ObjectManager $om, AclHelper $aclHelper)
    {
        parent::__construct($class, $om);

        $this->aclHelper = $aclHelper;
    }

    /**
     * @param User     $user
     * @param GridView $gridView
     * @param bool     $default
     */
    public function setDefaultGridView(User $user, GridView $gridView, $default)
    {
        /** @var GridViewRepository $repository */
        $repository = $this->getRepository();
        $gridViews  = $repository->findDefaultGridViews($this->aclHelper, $user, $gridView, false);

        foreach ($gridViews as $view) {
            $view->removeUser($user);
        }
        if ($default) {
            $gridView->addUser($user);
        }

        $this->getObjectManager()->flush();
    }
}
