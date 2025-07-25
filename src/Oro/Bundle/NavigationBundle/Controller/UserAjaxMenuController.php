<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The AJAX controller for the user menu.
 */
#[Route(path: '/menu/user')]
#[CsrfProtection()]
class UserAjaxMenuController extends AbstractAjaxMenuController
{
    #[\Override]
    protected function getAllowedContextKeys()
    {
        return [ScopeUserCriteriaProvider::USER];
    }

    #[\Override]
    protected function checkAcl(array $context)
    {
        if (!$this->isGranted(
            'oro_user_user_update',
            $context[ScopeUserCriteriaProvider::USER]
        )
        ) {
            throw $this->createAccessDeniedException();
        }
        parent::checkAcl($context);
    }

    #[\Override]
    protected function getMenu($menuName, array $context)
    {
        if (array_key_exists(ScopeUserCriteriaProvider::USER, $context)) {
            /** @var User $user */
            $user = $context[ScopeUserCriteriaProvider::USER];
            $context[ScopeOrganizationCriteriaProvider::ORGANIZATION] = $user->getOrganization();
        }

        return parent::getMenu($menuName, $context);
    }

    #[Route(path: '/reset/{menuName}', name: 'oro_navigation_user_menu_ajax_reset', methods: ['DELETE'])]
    #[\Override]
    public function resetAction($menuName, Request $request)
    {
        return parent::resetAction($menuName, $request);
    }

    #[Route(path: '/create/{menuName}/{parentKey}', name: 'oro_navigation_user_menu_ajax_create', methods: ['POST'])]
    #[\Override]
    public function createAction(Request $request, $menuName, $parentKey)
    {
        return parent::createAction($request, $menuName, $parentKey);
    }

    #[Route(path: '/delete/{menuName}/{key}', name: 'oro_navigation_user_menu_ajax_delete', methods: ['DELETE'])]
    #[\Override]
    public function deleteAction($menuName, $key, Request $request)
    {
        return parent::deleteAction($menuName, $key, $request);
    }

    #[Route(path: '/show/{menuName}/{key}', name: 'oro_navigation_user_menu_ajax_show', methods: ['PUT'])]
    #[\Override]
    public function showAction($menuName, $key, Request $request)
    {
        return parent::showAction($menuName, $key, $request);
    }

    #[Route(path: '/hide/{menuName}/{key}', name: 'oro_navigation_user_menu_ajax_hide', methods: ['PUT'])]
    #[\Override]
    public function hideAction($menuName, $key, Request $request)
    {
        return parent::hideAction($menuName, $key, $request);
    }

    #[Route(path: '/move/{menuName}', name: 'oro_navigation_user_menu_ajax_move', methods: ['PUT'])]
    #[\Override]
    public function moveAction(Request $request, $menuName)
    {
        return parent::moveAction($request, $menuName);
    }
}
