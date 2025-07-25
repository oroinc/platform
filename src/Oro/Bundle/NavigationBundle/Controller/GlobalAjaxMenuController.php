<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Ajax Global Menu Controller
 */
#[Route(path: '/menu/global')]
#[CsrfProtection()]
class GlobalAjaxMenuController extends AbstractAjaxMenuController
{
    #[\Override]
    protected function checkAcl(array $context)
    {
        if (!$this->isGranted('oro_config_system')) {
            throw $this->createAccessDeniedException();
        }
        parent::checkAcl($context);
    }

    #[Route(path: '/reset/{menuName}', name: 'oro_navigation_global_menu_ajax_reset', methods: ['DELETE'])]
    #[\Override]
    public function resetAction($menuName, Request $request)
    {
        return parent::resetAction($menuName, $request);
    }

    #[Route(path: '/create/{menuName}/{parentKey}', name: 'oro_navigation_global_menu_ajax_create', methods: ['POST'])]
    #[\Override]
    public function createAction(Request $request, $menuName, $parentKey)
    {
        return parent::createAction($request, $menuName, $parentKey);
    }

    #[Route(path: '/delete/{menuName}/{key}', name: 'oro_navigation_global_menu_ajax_delete', methods: ['DELETE'])]
    #[\Override]
    public function deleteAction($menuName, $key, Request $request)
    {
        return parent::deleteAction($menuName, $key, $request);
    }

    #[Route(path: '/show/{menuName}/{key}', name: 'oro_navigation_global_menu_ajax_show', methods: ['PUT'])]
    #[\Override]
    public function showAction($menuName, $key, Request $request)
    {
        return parent::showAction($menuName, $key, $request);
    }

    #[Route(path: '/hide/{menuName}/{key}', name: 'oro_navigation_global_menu_ajax_hide', methods: ['PUT'])]
    #[\Override]
    public function hideAction($menuName, $key, Request $request)
    {
        return parent::hideAction($menuName, $key, $request);
    }

    #[Route(path: '/move/{menuName}', name: 'oro_navigation_global_menu_ajax_move', methods: ['PUT'])]
    #[\Override]
    public function moveAction(Request $request, $menuName)
    {
        return parent::moveAction($request, $menuName);
    }
}
