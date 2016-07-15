<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
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

    /** @var TranslatorInterface */
    protected $gridViewManager;

    /**
     * @param Registry $registry
     * @param SecurityFacade $securityFacade
     * @param AclHelper $aclHelper
     * @param TranslatorInterface $translator
     * @param GridViewManager $gridViewManager
     */
    public function __construct(
        Registry $registry,
        SecurityFacade $securityFacade,
        AclHelper $aclHelper,
        TranslatorInterface $translator,
        GridViewManager $gridViewManager
    ) {
        $this->registry = $registry;
        $this->securityFacade = $securityFacade;
        $this->aclHelper = $aclHelper;
        $this->translator = $translator;
        $this->gridViewManager = $gridViewManager;
    }

    /**
     * @param GridViewsLoadEvent $event
     */
    public function onViewsLoad(GridViewsLoadEvent $event)
    {
        $gridName    = $event->getGridName();
        $views = [];
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            return;
        }

        $defaultGridView    = $this->gridViewManager->getDefaultView($currentUser, $gridName);
        $gridViews = $event->getGridViews();

        foreach ($gridViews['system'] as $systemView) {
            if ($defaultGridView) {
                if ($systemView->getName() == $defaultGridView->getName()) {
                    $systemView->setDefault(true);
                } else {
                    $systemView->setDefault(false);
                }
            }
            $views[] = $systemView->getMetadata();
        }
        foreach ($gridViews['user'] as $gridView) {
            $view = $gridView->createView();
            $view->setEditable($this->securityFacade->isGranted('EDIT', $gridView));
            $view->setDeletable($this->securityFacade->isGranted('DELETE', $gridView));
            if ($defaultGridView) {
                $view->setDefault($defaultGridView->getName() === $gridView->getName());
            }
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
