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
     * @Route("/", name="oro_navigation_global_menu_index")
     * @Template
     *
     * @return array
     */
    public function indexAction()
    {
        return $this->index();
    }

    /**
     * @Route("/{menuName}", name="oro_navigation_global_menu_view")
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
     * @Route("/{menuName}/create/{parentKey}", name="oro_navigation_global_menu_create")
     * @Template("OroNavigationBundle:GlobalMenu:update.html.twig")
     *
     * @param string      $menuName
     * @param string|null $parentKey
     *
     * @return array|RedirectResponse
     */
    public function createAction($menuName, $parentKey = null)
    {
        return parent::create($menuName, $parentKey);
    }

    /**
     * @Route("/{menuName}/update/{key}", name="oro_navigation_global_menu_update")
     * @Template
     *
     * @param string $menuName
     * @param string $key
     *
     * @return array|RedirectResponse
     */
    public function updateAction($menuName, $key)
    {
        return parent::update($menuName, $key);
    }

    /**
     * {@inheritDoc}
     */
    protected function checkAcl()
    {
        if (!$this->get('oro_security.security_facade')->isGranted('oro_config_system')) {
            throw $this->createAccessDeniedException();
        }
        parent::checkAcl();
    }
}
