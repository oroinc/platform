<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;

/**
 * @Route("/menuupdate")
 */
class MenuUpdateController extends Controller
{
    /**
     * @Route(
     *     "/{menu}/create/{parentKey}",
     *     name="oro_navigation_menu_update_create",
     *     defaults={"parentKey" = null},
     *     requirements={"menu" = "[-_\w]+", "parentKey" = "[-_w]+"}
     * )
     * @Template("OroNavigationBundle:MenuUpdate:update.html.twig")
     * @Acl(
     *     id="oro_navigation_menu_update_create",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="CREATE"
     * )
     *
     * @param string $menu
     * @param string|null $parentKey
     * @return array|RedirectResponse
     */
    public function createAction($menu, $parentKey)
    {
        return $this->update($menu, new MenuUpdate());
    }

    /**
     * @Route(
     *     "/{menu}/update/{key}/{parentKey}",
     *     name="oro_navigation_menu_update_update",
     *     defaults={"parentKey" = null},
     *     requirements={"menu" = "[-_\w]+", "parentKey" = "[-_w]+"}
     * )
     * @Template()
     * @Acl(
     *     id="oro_navigation_menu_update_update",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="EDIT"
     * )
     *
     * @param string $menu
     * @param string $key
     * @param string $parentKey
     * @return array|RedirectResponse
     */
    public function updateAction($menu, $key, $parentKey)
    {
        return $this->update($menu, new MenuUpdate());
    }

    /**
     * @param string $menu
     * @param MenuUpdate $menuUpdate
     * @return array|RedirectResponse
     */
    private function update($menu, MenuUpdate $menuUpdate)
    {
        $form = $this->createForm(MenuUpdateType::NAME, $menuUpdate);

        $response = $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );
        if (is_array($response)) {
            $response['menu'] = $menu;
            $response['tree'] = $this->getTree($menu);
        }

        return $response;
    }

    /**
     * @Route("/", name="oro_navigation_menu_update_index")
     * @Template()
     * @Acl(
     *     id="oro_navigation_menu_update_view",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="VIEW"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/{menu}", name="oro_navigation_menu_update_view", requirements={"menu" = "[-_\w]+"})
     * @Template()
     * @AclAncestor("oro_navigation_menu_update_view")
     *
     * @param string $menu
     * @return array
     */
    public function viewAction($menu)
    {
        $root = $this->get('oro_menu.builder_chain')->get($menu);
        if (!count($root->getChildren())) {
            throw $this->createNotFoundException("Menu '$menu' not found");
        }

        return [
            'entity' => $root,
            'menu' => $menu,
            'tree' => $this->getTree($menu),
        ];
    }

    /**
     * @param string $menu
     * @return array
     */
    public function getTree($menu)
    {
        $root = $this->get('oro_menu.builder_chain')->get($menu);

        return $this->get('oro_navigation.tree.menu_update_tree_handler')->createTree($root);
    }
}
