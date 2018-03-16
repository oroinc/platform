<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Entity\Manager\AppearanceTypeManager;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\DataGridBundle\Event\GridViewsLoadEvent;
use Oro\Bundle\DataGridBundle\Extension\Appearance\Configuration;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class GridViewsLoadListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var GridViewManager */
    protected $gridViewManager;

    /**
     * @var AppearanceTypeManager
     */
    protected $appearanceTypeManager;

    /**
     * @param ManagerRegistry               $registry
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param AclHelper                     $aclHelper
     * @param TranslatorInterface           $translator
     * @param GridViewManager               $gridViewManager
     * @param AppearanceTypeManager         $appearanceTypeManager
     */
    public function __construct(
        ManagerRegistry $registry,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        AclHelper $aclHelper,
        TranslatorInterface $translator,
        GridViewManager $gridViewManager,
        AppearanceTypeManager $appearanceTypeManager
    ) {
        $this->registry = $registry;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->aclHelper = $aclHelper;
        $this->translator = $translator;
        $this->gridViewManager = $gridViewManager;
        $this->appearanceTypeManager = $appearanceTypeManager;
    }

    /**
     * @param GridViewsLoadEvent $event
     */
    public function onViewsLoad(GridViewsLoadEvent $event)
    {
        $gridName = $event->getGridName();
        $views = [];
        $currentUser = $this->tokenAccessor->getUser();
        if (null === $currentUser) {
            return;
        }

        $defaultGridView = $this->gridViewManager->getDefaultView($currentUser, $gridName);
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
            $view->setEditable($this->authorizationChecker->isGranted('EDIT', $gridView));
            $view->setDeletable($this->authorizationChecker->isGranted('DELETE', $gridView));
            if ($defaultGridView) {
                $view->setDefault($defaultGridView->getName() === $gridView->getName());
            }
            if ($gridView->getOwner() && $gridView->getOwner()->getId() !== $currentUser->getId()) {
                $view->setSharedBy($gridView->getOwner()->getUsername());
            }
            $views[] = $view->getMetadata();
        }

        foreach ($views as &$view) {
            if (!$view['icon']) {
                $view['icon'] = $this->getViewIcon($view['appearanceType']);
            }
        }

        $event->setGridViews($views);
    }

    /**
     * @param string $appearanceType
     * @return string
     */
    protected function getViewIcon($appearanceType)
    {
        if (!$appearanceType) {
            $appearanceType = Configuration::GRID_APPEARANCE_TYPE;
        }
        $types = $this->appearanceTypeManager->getAppearanceTypes();
        $icon = isset($types[$appearanceType]['icon']) ? $types[$appearanceType]['icon'] : '';

        return $icon;
    }

    /**
     * @return GridViewRepository
     */
    protected function getGridViewRepository()
    {
        return $this->registry->getRepository('OroDataGridBundle:GridView');
    }
}
