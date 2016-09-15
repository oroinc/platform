<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @Route("/menuupdate")
 */
class MenuUpdateController extends Controller
{
    /**
     * @Route("/grid", name="oro_navigation_menu_update_grid")
     * @Template("OroNavigationBundle:MenuUpdate:grid.html.twig")
     * @Acl(
     *      id="oro_navigation_menu_grid",
     *      type="entity",
     *      class="OroNavigationBundle:MenuUpdate",
     *      permission="VIEW"
     * )
     */
    public function gridAction()
    {
        return [];
    }
}
