<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @Route("/menu/global")
 */
class GlobalMenuController extends AbstractMenuController
{
    /**
     * @Route("/", name="oro_navigation_org_menu_index")
     * @Template
     *
     * @return array
     */
    public function indexAction()
    {
        return $this->index();
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
        return $this->view($menuName);
    }

    /**
     * @Route("/{menuName}/create/{parentKey}", name="oro_navigation_org_menu_create")
     * @Template("OroNavigationBundle:GlobalMenu:update.html.twig")
     *
     * @param string $menuName
     * @param string|null $parentKey
     *
     * @return array|RedirectResponse
     */
    public function createAction($menuName, $parentKey = null)
    {
        return parent::create($menuName, $parentKey, $this->getOwnerId());
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
        return parent::update($menuName, $key, $this->getOwnerId());
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnershipType()
    {
        return $this->getOwnershipProvider()->getType();
    }

    /**
     * {@inheritDoc}
     */
    protected function getOwnerId()
    {
        return $this->getOwnershipProvider()->getId();
    }

    /**
     * @return \Oro\Bundle\NavigationBundle\Menu\Provider\GlobalOwnershipProvider
     */
    protected function getOwnershipProvider()
    {
        return $this->get('oro_navigation.ownership_provider.global');
    }
}
