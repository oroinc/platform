<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/menuupdate")
 */
class MenuUpdateController extends Controller
{
    /** @var MenuUpdateManager */
    private $manager;

    /**
     * @Route("/", name="oro_navigation_menu_update_index")
     * @Template
     * @AclAncestor("oro_navigation_menu_update_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => MenuUpdate::class
        ];
    }

    /**
     * @Route("/{menuName}", name="oro_navigation_menu_update_view")
     * @Template
     * @Acl(
     *     id="oro_navigation_menu_update_view",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="VIEW"
     * )
     *
     * @param string $menuName
     * @return array
     */
    public function viewAction($menuName)
    {
        $menu = $this->getMenu($menuName);

        return $this->getResponse(['entity' => $menu], $menu);
    }

    /**
     * @Route("/{menuName}/create/{parentKey}", name="oro_navigation_menu_update_create")
     * @Template("OroNavigationBundle:MenuUpdate:update.html.twig")
     * @Acl(
     *     id="oro_navigation_menu_update_create",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="CREATE"
     * )
     *
     * @param string $menuName
     * @param string|null $parentKey
     * @return array|RedirectResponse
     */
    public function createAction($menuName, $parentKey = null)
    {
        /** @var MenuUpdate $menuUpdate */
        $menuUpdate = $this->getManager()->createMenuUpdate(MenuUpdate::OWNERSHIP_USER, $this->getUser()->getId());

        if ($parentKey) {
            $parent = $this->getMenuUpdate($menuName, $parentKey, true);
            $menuUpdate->setParentKey($parent->getKey());
        }

        $menuUpdate->setMenu($menuName);

        $menu = $this->getMenu($menuName);

        return $this->getResponse($this->update($menuUpdate), $menu);
    }

    /**
     * @Route("/{menuName}/update/{key}", name="oro_navigation_menu_update_update")
     * @Template
     * @Acl(
     *     id="oro_navigation_menu_update_update",
     *     type="entity",
     *     class="OroNavigationBundle:MenuUpdate",
     *     permission="EDIT"
     * )
     *
     * @param string $menuName
     * @param string $key
     * @return array|RedirectResponse
     */
    public function updateAction($menuName, $key)
    {
        $menuUpdate = $this->getMenuUpdate($menuName, $key);
        $menu = $this->getMenu($menuName);

        return $this->getResponse($this->update($menuUpdate), $menu);
    }

    /**
     * @param MenuUpdate $menuUpdate
     * @return array|RedirectResponse
     */
    private function update(MenuUpdate $menuUpdate)
    {
        $form = $this->createForm(MenuUpdateType::NAME, $menuUpdate, ['menu_update_key' => $menuUpdate->getKey()]);

        return $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );
    }

    /**
     * @param array|RedirectResponse $response
     * @param ItemInterface $menu
     * @return array|RedirectResponse
     */
    private function getResponse($response, ItemInterface $menu)
    {
        if (is_array($response)) {
            $treeHandler = $this->get('oro_navigation.tree.menu_update_tree_handler');

            $response['menuName'] = $menu->getName();
            $response['tree'] = $treeHandler->createTree($menu);
        }

        return $response;
    }

    /**
     * @param string $menuName
     * @param string $key
     * @param bool $isExist
     * @return MenuUpdate
     */
    private function getMenuUpdate($menuName, $key, $isExist = false)
    {
        $menuUpdate = $this->getManager()->getMenuUpdateByKeyAndScope(
            $menuName,
            $key,
            MenuUpdate::OWNERSHIP_USER,
            $this->getUser()->getId()
        );

        if ($isExist && !$menuUpdate->getKey()) {
            throw $this->createNotFoundException(
                sprintf("Item \"%s\" in \"%s\" not found.", $key, $menuName)
            );
        }

        return $menuUpdate;
    }

    /**
     * @param string $menuName
     * @return ItemInterface
     */
    private function getMenu($menuName)
    {
        $menu = $this->getManager()->getMenu($menuName);
        if (!count($menu->getChildren())) {
            throw $this->createNotFoundException(sprintf("Menu \"%s\" not found.", $menuName));
        }

        return $menu;
    }

    /**
     * @return MenuUpdateManager
     */
    private function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->get('oro_navigation.manager.menu_update_default');
        }
        return $this->manager;
    }
}
