<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Event\GridViewsLoadEvent;
use Oro\Bundle\DataGridBundle\Entity\GridView;
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

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param Registry $registry
     * @param SecurityFacade $securityFacade
     * @param AclHelper $aclHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Registry $registry,
        SecurityFacade $securityFacade,
        AclHelper $aclHelper,
        TranslatorInterface $translator
    ) {
        $this->registry = $registry;
        $this->securityFacade = $securityFacade;
        $this->aclHelper = $aclHelper;
        $this->translator = $translator;
    }

    /**
     * @param GridViewsLoadEvent $event
     */
    public function onViewsLoad(GridViewsLoadEvent $event)
    {
        $gridName    = $event->getGridName();
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            return;
        }

        $gridViewRepository = $this->getGridViewRepository();
        $gridViews          = $gridViewRepository->findGridViews($this->aclHelper, $currentUser, $gridName);
        if (!$gridViews) {
            return;
        }
        $defaultGridView    = $gridViewRepository->findDefaultGridView($this->aclHelper, $currentUser, $gridName);
        $views = $event->getGridViews();
        foreach ($gridViews as $gridView) {
            $view = $gridView->createView();
            $view->setEditable($this->securityFacade->isGranted('EDIT', $gridView));
            $view->setDeletable($this->securityFacade->isGranted('DELETE', $gridView));
            $view->setDefault($defaultGridView === $gridView);
            if ($gridView->getOwner() && $gridView->getOwner()->getId() !== $currentUser->getId()) {
                $view->setSharedBy($gridView->getOwner()->getUsername());
            }
            $views[]   = $view->getMetadata();
        }

        $event->setGridViews($views);
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
