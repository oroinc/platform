<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
     * @return int
     */
    abstract protected function getOwnershipType();

    public function indexAction()
    {
        return [
            'ownershipType' => $this->getOwnershipType(),
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
     *
     * @return array|RedirectResponse
     */
    public function createAction($menuName, $parentKey = null)
    {
        /** @var MenuUpdate $menuUpdate */
        $menuUpdate = $this->getManager()->createMenuUpdate($this->getOwnershipType(), $this->getUser()->getId());

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
        $menuUpdate = $this->getMenuUpdate($menuName, $key);
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
     *
     * @return array|RedirectResponse
     */
    private function getResponse($response, ItemInterface $menu)
    {
        if (is_array($response)) {
            $treeHandler = $this->get('oro_navigation.tree.menu_update_tree_handler');

            $response['ownershipType'] = $this->getOwnershipType();
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
        if ($this->getOwnershipType() == MenuUpdate::OWNERSHIP_ORGANIZATION) {
            $ownerId = $this->getCurrentOrganization()->getId();
        } else {
            $ownerId = $this->getCurrentUser()->getId();
        }

        $menuUpdate = $this->getManager()->getMenuUpdateByKeyAndScope(
            $menuName,
            $key,
            $this->getOwnershipType(),
            $ownerId
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
     */
    protected function getMenu($menuName)
    {
        $options = [
            'ignoreCache' => true,
            'ownershipType' => $this->getOwnershipType()
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

    /**
     * @return null|User
     */
    protected function getCurrentUser()
    {
        $user = $this->get('oro_security.security_facade')->getLoggedUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * @return null|Organization
     */
    protected function getCurrentOrganization()
    {
        $organization = $this->get('oro_security.security_facade')->getOrganization();
        if (!is_bool($organization)) {
            return $organization;
        }

        return null;
    }
}
