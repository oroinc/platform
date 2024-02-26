<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The AJAX controller for the user menu.
 */
#[Route(path: '/menu/user')]
#[CsrfProtection()]
class UserAjaxMenuController extends AbstractAjaxMenuController
{
    /**
     * {@inheritDoc}
     */
    protected function getAllowedContextKeys()
    {
        return [ScopeUserCriteriaProvider::USER];
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    protected function getMenu($menuName, array $context)
    {
        if (array_key_exists(ScopeUserCriteriaProvider::USER, $context)) {
            /** @var User $user */
            $user = $context[ScopeUserCriteriaProvider::USER];
            $context[ScopeOrganizationCriteriaProvider::ORGANIZATION] = $user->getOrganization();
        }

        return parent::getMenu($menuName, $context);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/reset/{menuName}', name: 'oro_navigation_user_menu_ajax_reset', methods: ['DELETE'])]
    public function resetAction($menuName, Request $request)
    {
        return parent::resetAction($menuName, $request);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/create/{menuName}/{parentKey}', name: 'oro_navigation_user_menu_ajax_create', methods: ['POST'])]
    public function createAction(Request $request, $menuName, $parentKey)
    {
        return parent::createAction($request, $menuName, $parentKey);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/delete/{menuName}/{key}', name: 'oro_navigation_user_menu_ajax_delete', methods: ['DELETE'])]
    public function deleteAction($menuName, $key, Request $request)
    {
        return parent::deleteAction($menuName, $key, $request);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/show/{menuName}/{key}', name: 'oro_navigation_user_menu_ajax_show', methods: ['PUT'])]
    public function showAction($menuName, $key, Request $request)
    {
        return parent::showAction($menuName, $key, $request);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/hide/{menuName}/{key}', name: 'oro_navigation_user_menu_ajax_hide', methods: ['PUT'])]
    public function hideAction($menuName, $key, Request $request)
    {
        return parent::hideAction($menuName, $key, $request);
    }

    /**
     * {@inheritdoc}
     */
    #[Route(path: '/move/{menuName}', name: 'oro_navigation_user_menu_ajax_move', methods: ['PUT'])]
    public function moveAction(Request $request, $menuName)
    {
        return parent::moveAction($request, $menuName);
    }
}
