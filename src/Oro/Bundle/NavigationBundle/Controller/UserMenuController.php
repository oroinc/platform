<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/menu/user")
 */
class UserMenuController extends AbstractMenuController
{
    /**
     * @Route("/", name="oro_navigation_user_menu_index")
     * @Template
     * @AclAncestor("oro_navigation_manage_menus")
     *
     * @return array
     */
    public function indexAction()
    {
        return parent::index();
    }

    /**
     * @Route("/{menuName}", name="oro_navigation_user_menu_view")
     * @Template
     * @AclAncestor("oro_navigation_manage_menus")
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
     * @Template("OroNavigationBundle:UserMenu:update.html.twig")
     * @AclAncestor("oro_navigation_manage_menus")
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
     * @AclAncestor("oro_navigation_manage_menus")
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
     * @return array
     */
    protected function getContext()
    {
        return ['user' => $this->getUser()->getId()];
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopeType()
    {
        return 'menu_default_visibility';
    }

    /**
     * {@inheritdoc}
     */
    protected function getManager()
    {
        return $this->get('oro_navigation.manager.menu_update_default');
    }

    /**
     * {@inheritdoc}
     */
    protected function getContextForMenuUpdateBuilder()
    {
        return [
            'organization' => $this->getUser()->getCurrentOrganization()->getId(),
            'user' => $this->getUser()->getId()
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function checkAcl()
    {
        if (!$this->get('oro_security.security_facade')->isGranted('oro_user_user_update')) {
            throw $this->createAccessDeniedException();
        }
    }
}
