<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;
use Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The controller for the user menu.
 */
#[Route(path: '/menu/user')]
class UserMenuController extends AbstractMenuController
{
    /**
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_navigation_user_menu_index')]
    #[Template('@OroNavigation/UserMenu/index.html.twig')]
    public function indexAction()
    {
        return parent::index($this->getContext());
    }

    /**
     *
     * @param string $menuName
     *
     * @return array
     */
    #[Route(path: '/{menuName}', name: 'oro_navigation_user_menu_view')]
    #[Template('@OroNavigation/UserMenu/view.html.twig')]
    public function viewAction($menuName)
    {
        return parent::view($menuName, $this->getContext());
    }

    /**
     *
     * @param string $menuName
     * @param string|null $parentKey
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/{menuName}/create/{parentKey}', name: 'oro_navigation_user_menu_create')]
    #[Template('@OroNavigation/UserMenu/update.html.twig')]
    public function createAction($menuName, $parentKey = null)
    {
        return parent::create($menuName, $parentKey, $this->getContext());
    }

    /**
     *
     * @param string $menuName
     * @param string $key
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/{menuName}/update/{key}', name: 'oro_navigation_user_menu_update')]
    #[Template('@OroNavigation/UserMenu/update.html.twig')]
    public function updateAction($menuName, $key)
    {
        return parent::update($menuName, $key, $this->getContext());
    }

    /**
     *
     * @param Request $request
     * @param string  $menuName
     * @return array|RedirectResponse
     */
    #[Route(path: '/{menuName}/move', name: 'oro_navigation_user_menu_move')]
    public function moveAction(Request $request, $menuName)
    {
        return parent::move($request, $menuName, $this->getContext());
    }

    /**
     * @return array
     */
    private function getContext()
    {
        return [ScopeUserCriteriaProvider::USER => $this->getUser()->getId()];
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
    protected function getMenu(string $menuName, array $context): ItemInterface
    {
        if (array_key_exists(ScopeUserCriteriaProvider::USER, $context)) {
            /** @var User $user */
            $user = $context[ScopeUserCriteriaProvider::USER];
            $context[ScopeOrganizationCriteriaProvider::ORGANIZATION] = $user->getOrganization();
        }

        return parent::getMenu($menuName, $context);
    }
}
