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
        $gridName = $event->getGridName();
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            return;
        }

        $gridViewRepository = $this->getGridViewRepository();
        $gridViews          = $gridViewRepository->findGridViews($this->aclHelper, $currentUser, $gridName);
        $defaultGridView    = $gridViewRepository->findUserDefaultGridView($this->aclHelper, $currentUser, $gridName);
        if (!$gridViews) {
            return;
        }
        $choices = [];
        $views = [];
        foreach ($gridViews as $gridView) {
            $view = $gridView->createView();
            $view->setEditable($this->securityFacade->isGranted('EDIT', $gridView));
            $view->setDeletable($this->securityFacade->isGranted('DELETE', $gridView));
            $view->setIsDefault($defaultGridView === $gridView);
            $views[] = $view->getMetadata();
            $choices[] = [
                'label' => $this->createGridViewLabel($currentUser, $gridView),
                'value' => $gridView->getId(),
            ];
        }

        $newGridViews = $event->getGridViews();
        $newGridViews['choices'] = array_merge($newGridViews['choices'], $choices);
        $newGridViews['views'] = array_merge($newGridViews['views'], $views);
        $newGridViews['default'] = $defaultGridView;

        $event->setGridViews($newGridViews);
    }

    /**
     * @param User $currentUser
     * @param GridView $gridView
     *
     * @return string
     */
    protected function createGridViewLabel(User $currentUser, GridView $gridView)
    {
        if ($gridView->getOwner()->getId() === $currentUser->getId()) {
            return $gridView->getName();
        }

        return $this->translator->trans(
            'oro.datagrid.gridview.shared_by',
            [
                '%name%'  =>  $gridView->getName(),
                '%owner%' => $gridView->getOwner()->getUsername(),
            ]
        );
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
