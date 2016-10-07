<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oro\Bundle\NavigationBundle\Menu\Provider\OwnershipProviderInterface;
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

    /**
     * @return OwnershipProviderInterface
     */
    abstract protected function getOwnershipProvider();

    /**
     * @return array
     */
    public function indexAction()
    {
        return [
            'ownershipType' => $this->getOwnershipProvider()->getType(),
            'entityClass' => MenuUpdate::class
        ];
    }

    /**
     * @param string $menuName
     *
     * @return array|RedirectResponse
     */
    public function viewAction($menuName)
    {
        $menu = $this->getMenu($menuName);

        return $this->getResponse(['entity' => $menu], $menu);
    }

    /**
     * @param string $menuName
     * @param string|null $parentKey
     * @param bool $isDivider
     *
     * @return array|RedirectResponse
     */
    public function createAction($menuName, $parentKey = null, $isDivider = false)
    {
        $provider = $this->getOwnershipProvider();
        /** @var MenuUpdate $menuUpdate */
        $menuUpdate = $this->getManager()->createMenuUpdate($provider->getType(), $provider->getId());

        if ($isDivider) {
            $menuUpdate->setDivider($isDivider);
            $menuUpdate->setDefaultTitle('--------');
            $menuUpdate->setUri('#');
        }

        if ($parentKey) {
            $parent = $this->getMenuUpdate($menuName, $parentKey, true);
            $menuUpdate->setParentKey($parent->getKey());
        }

        $menuUpdate->setMenu($menuName);

        $menu = $this->getMenu($menuName);

        return $this->getResponse($this->update($menuUpdate), $menu);
    }

    /**
     * @param string $menuName
     * @param string $key
     *
     * @return array|RedirectResponse
     */
    public function updateAction($menuName, $key)
    {
        $menuUpdate = $this->getMenuUpdate($menuName, $key, true);
        $menu = $this->getMenu($menuName);

        return $this->getResponse($this->update($menuUpdate), $menu);
    }

    /**
     * @param MenuUpdateInterface $menuUpdate
     *
     * @return array|RedirectResponse
     */
    protected function update(MenuUpdateInterface $menuUpdate)
    {
        $form = $this->createForm(MenuUpdateType::NAME, $menuUpdate);

        return $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );
    }

    /**
     * @param array|RedirectResponse $response
     * @param ItemInterface $menu
     *
     * @return array|RedirectResponse
     */
    private function getResponse($response, ItemInterface $menu)
    {
        if (is_array($response)) {
            $treeHandler = $this->get('oro_navigation.tree.menu_update_tree_handler');

            $response['ownershipType'] = $this->getOwnershipProvider()->getType();
            $response['menuName'] = $menu->getName();
            $response['tree'] = $treeHandler->createTree($menu);
        }

        return $response;
    }

    /**
     * @param string $menuName
     * @param string $key
     * @param bool $isExist
     *
     * @return MenuUpdateInterface
     */
    protected function getMenuUpdate($menuName, $key, $isExist = false)
    {
        $provider = $this->getOwnershipProvider();

        $menuUpdate = $this->getManager()->getMenuUpdateByKeyAndScope(
            $menuName,
            $key,
            $provider->getType(),
            $provider->getId()
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
     *
     * @return ItemInterface
     * @throws NotFoundHttpException
     */
    protected function getMenu($menuName)
    {
        $options = [
            'ownershipType' => $this->getOwnershipProvider()->getType()
        ];
        $menu = $this->getManager()->getMenu($menuName, $options);
        if (!count($menu->getChildren())) {
            throw $this->createNotFoundException(sprintf("Menu \"%s\" not found.", $menuName));
        }

        return $menu;
    }

    /**
     * @return MenuUpdateManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->get('oro_navigation.manager.menu_update_default');
        }
        return $this->manager;
    }
}
