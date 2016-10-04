<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

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
     * @AclAncestor("oro_navigation_manage_menus")
     *
     * @return array
     */
    public function indexAction()
    {
        $this->checkAcl();

        return parent::indexAction();
    }

    /**
     * @Route("/{menuName}", name="oro_navigation_org_menu_view")
     * @Template
     * @AclAncestor("oro_navigation_manage_menus")
     *
     * @param string $menuName
     *
     * @return array
     */
    public function viewAction($menuName)
    {
        $this->checkAcl();

        return parent::viewAction($menuName);
    }

    /**
     * @Route("/{menuName}/create/{parentKey}", name="oro_navigation_org_menu_create")
     * @Template("OroNavigationBundle:OrganizationMenu:update.html.twig")
     * @AclAncestor("oro_navigation_manage_menus")
     *
     * @param string $menuName
     * @param string|null $parentKey
     *
     * @return array|RedirectResponse
     */
    public function createAction($menuName, $parentKey = null)
    {
        $this->checkAcl();

        return parent::createAction($menuName, $parentKey);
    }

    /**
     * @Route("/{menuName}/update/{key}", name="oro_navigation_org_menu_update")
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
        $this->checkAcl();

        return parent::updateAction($menuName, $key);
    }

    /**
     * @throws AccessDeniedException
     */
    private function checkAcl()
    {
        if (!$this->get('oro_security.security_facade')->isGranted('oro_config_system')) {
            throw new AccessDeniedException('Insufficient permission');
        }
    }
}
