<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;

/**
 * @Route("/menu/organization")
 */
class OrganizationMenuController extends AbstractMenuController
{
    /**
     * {@inheritdoc}
     */
    protected function getOwnershipType()
    {
        return MenuUpdate::OWNERSHIP_ORGANIZATION;
    }

    /**
     * @Route("/", name="oro_navigation_org_menu_index")
     * @Template
     *
     * @return array
     */
    public function indexAction()
    {
        return parent::indexAction();
    }

    /**
     * @Route("/{menuName}", name="oro_navigation_org_menu_view")
     * @Template
     *
     * @param string $menuName
     *
     * @return array
     */
    public function viewAction($menuName)
    {
        return parent::viewAction($menuName);
    }

    /**
     * @Route("/{menuName}/create/{parentKey}", name="oro_navigation_org_menu_create")
     * @Template("OroNavigationBundle:OrganizationMenu:update.html.twig")
     *
     * @param string $menuName
     * @param string|null $parentKey
     * @param bool $isDivider
     *
     * @return array|RedirectResponse
     */
    public function createAction($menuName, $parentKey = null, $isDivider = false)
    {
        return parent::createAction($menuName, $parentKey, $isDivider);
    }

    /**
     * @Route("/{menuName}/create_divider/{parentKey}", name="oro_navigation_org_menu_create_divider")
     * @Template("OroNavigationBundle:UserMenu:update.html.twig")
     *
     * @param string $menuName
     * @param string $parentKey
     *
     * @return RedirectResponse
     */
    public function createDividerAction($menuName, $parentKey = null)
    {
        return $this->createAction($menuName, $parentKey, true);
    }

    /**
     * @Route("/{menuName}/update/{key}", name="oro_navigation_org_menu_update")
     * @Template
     *
     * @param string $menuName
     * @param string $key
     *
     * @return array|RedirectResponse
     */
    public function updateAction($menuName, $key)
    {
        return parent::updateAction($menuName, $key);
    }
}
