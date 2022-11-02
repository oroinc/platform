<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;
use Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for the user menu.
 * @Route("/menu/user")
 */
class UserMenuController extends AbstractMenuController
{
    /**
     * @Route("/", name="oro_navigation_user_menu_index")
     * @Template
     *
     * @return array
     */
    public function indexAction()
    {
        return parent::index($this->getContext());
    }

    /**
     * @Route("/{menuName}", name="oro_navigation_user_menu_view")
     * @Template
     *
     * @param string $menuName
     *
     * @return array
     */
    public function viewAction($menuName)
    {
        return parent::view($menuName, $this->getContext());
    }

    /**
     * @Route("/{menuName}/create/{parentKey}", name="oro_navigation_user_menu_create")
     * @Template("@OroNavigation/UserMenu/update.html.twig")
     *
     * @param string $menuName
     * @param string|null $parentKey
     *
     * @return array|RedirectResponse
     */
    public function createAction($menuName, $parentKey = null)
    {
        return parent::create($menuName, $parentKey, $this->getContext());
    }

    /**
     * @Route("/{menuName}/update/{key}", name="oro_navigation_user_menu_update")
     * @Template
     *
     * @param string $menuName
     * @param string $key
     *
     * @return array|RedirectResponse
     */
    public function updateAction($menuName, $key)
    {
        return parent::update($menuName, $key, $this->getContext());
    }

    /**
     * @Route("/{menuName}/move", name="oro_navigation_user_menu_move")
     *
     * @param Request $request
     * @param string  $menuName
     *
     * @return array|RedirectResponse
     */
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
