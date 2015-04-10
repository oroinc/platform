<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Event\GridViewsLoadEvent;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class GridViewsLoadListener
{
    /** @var Registry */
    protected $registry;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param Registry $registry
     * @param SecurityFacade $securityFacade
     * @param AclHelper $aclHelper
     */
    public function __construct(Registry $registry, SecurityFacade $securityFacade, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->securityFacade = $securityFacade;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param GridViewsLoadEvent $event
     */
    public function onViewsLoad(GridViewsLoadEvent $event)
    {
        $gridName = $event->getGridName();
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            return;
        }

        $gridViews = $this->getGridViewRepository()->findGridViews($this->aclHelper, $gridName);
        if (!$gridViews) {
            return;
        }

        $choices = [];
        $views = [];
        foreach ($gridViews as $gridView) {
            $view = $gridView->createView();
            if ($this->securityFacade->isGranted('EDIT', $gridView)) {
                $view->setEditable();
            }
            if ($this->securityFacade->isGranted('DELETE', $gridView)) {
                $view->setDeletable();
            }

            $views[] = $view->getMetadata();
            $choices[] = [
                'label' => $gridView->getName(),
                'value' => $gridView->getId(),
            ];
        }

        $newGridViews = $event->getGridViews();
        $newGridViews['choices'] = array_merge($newGridViews['choices'], $choices);
        $newGridViews['views'] = array_merge($newGridViews['views'], $views);

        $event->setGridViews($newGridViews);
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * @return GridViewRepository
     */
    protected function getGridViewRepository()
    {
        return $this->registry->getRepository('OroDataGridBundle:GridView');
    }
}
