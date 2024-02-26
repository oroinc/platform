<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ajax Global Menu Controller
 */
#[Route(path: '/menu/global')]
#[CsrfProtection()]
class GlobalAjaxMenuController extends AbstractAjaxMenuController
{
    /**
     * {@inheritDoc}
     */
    protected function checkAcl(array $context)
    {
        if (!$this->isGranted('oro_config_system')) {
            throw $this->createAccessDeniedException();
        }
        parent::checkAcl($context);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/reset/{menuName}', name: 'oro_navigation_global_menu_ajax_reset', methods: ['DELETE'])]
    public function resetAction($menuName, Request $request)
    {
        return parent::resetAction($menuName, $request);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/create/{menuName}/{parentKey}', name: 'oro_navigation_global_menu_ajax_create', methods: ['POST'])]
    public function createAction(Request $request, $menuName, $parentKey)
    {
        return parent::createAction($request, $menuName, $parentKey);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/delete/{menuName}/{key}', name: 'oro_navigation_global_menu_ajax_delete', methods: ['DELETE'])]
    public function deleteAction($menuName, $key, Request $request)
    {
        return parent::deleteAction($menuName, $key, $request);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/show/{menuName}/{key}', name: 'oro_navigation_global_menu_ajax_show', methods: ['PUT'])]
    public function showAction($menuName, $key, Request $request)
    {
        return parent::showAction($menuName, $key, $request);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/hide/{menuName}/{key}', name: 'oro_navigation_global_menu_ajax_hide', methods: ['PUT'])]
    public function hideAction($menuName, $key, Request $request)
    {
        return parent::hideAction($menuName, $key, $request);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/move/{menuName}', name: 'oro_navigation_global_menu_ajax_move', methods: ['PUT'])]
    public function moveAction(Request $request, $menuName)
    {
        return parent::moveAction($request, $menuName);
    }
}
