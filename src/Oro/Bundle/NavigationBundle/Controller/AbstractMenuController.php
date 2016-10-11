<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;

abstract class AbstractMenuController extends Controller
{
    /**
     * @var MenuUpdateManager
     */
    protected $manager;

    abstract protected function getOwnershipType();

    protected function index()
    {
        return [
            'ownershipType' => $this->getOwnershipType(),
            'entityClass' => MenuUpdate::class
        ];
    }

    /**
     * @param $menuName
     * @param $response
     * @return mixed
     */
    protected function view($menuName)
    {
        $menu = $this->getMenu($menuName);

        return [
            'entity' => $menu,
            'tree' => $this->get('oro_navigation.tree.menu_update_tree_handler')->createTree($menu)
        ];
    }

    /**
     * @param string $menuName
     * @param string $parentKey
     * @return array|RedirectResponse
     */
    protected function create($menuName, $parentKey, $ownerId)
    {
        /** @var MenuUpdate $menuUpdate */
        $menuUpdate = $this->getManager()->createMenuUpdate(
            $this->getOwnershipType(),
            $ownerId,
            [
                'menu' => $menuName,
                'parentKey' => $parentKey
            ]
        );

        return $this->handleUpdate($menuUpdate);
    }

    /**
     * @param $menuName
     * @param $key
     * @return array|RedirectResponse
     *
     */
    protected function update($menuName, $key, $ownerId)
    {
        $menuUpdate = $this->getManager()->getMenuUpdateByKeyAndScope(
            $menuName,
            $key,
            $this->getOwnershipType(),
            $ownerId
        );

        if (!$menuUpdate->getKey()) {
            throw $this->createNotFoundException(
                sprintf("Item \"%s\" in \"%s\" not found.", $key, $menuName)
            );
        }

        return $this->handleUpdate($menuUpdate);
    }

    /**
     * @param $menuUpdate
     * @return array|RedirectResponse
     */
    protected function handleUpdate(MenuUpdateInterface $menuUpdate)
    {
        $form = $this->createForm(MenuUpdateType::NAME, $menuUpdate);

        $response = $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );

        if (is_array($response)) {
            $menu = $this->getMenu($menuUpdate->getMenu());

            $response['ownershipType'] = $this->getOwnershipType();
            $response['menuName'] = $menu->getName();
            $response['tree'] = $this->get('oro_navigation.tree.menu_update_tree_handler')->createTree($menu);
        }
        return $response;
    }


    /**
     * @return MenuUpdateManager
     */
    protected function getManager()
    {
        return $this->get('oro_navigation.manager.menu_update_default');
    }

    /**
     * @param $menuName
     * @return \Knp\Menu\ItemInterface
     */
    protected function getMenu($menuName)
    {
        $options = [
            'ownershipType' => $this->getOwnershipType()
        ];
        $menu = $this->getManager()->getMenu($menuName, $options);
        if (!count($menu->getChildren())) {
            throw $this->createNotFoundException(sprintf("Menu \"%s\" not found.", $menuName));
        }

        return $menu;
    }

}
