<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;

/**
 * @Route("/menuupdate")
 */
class MenuUpdateController extends Controller
{
    /**
     * @Route(
     *     "/{menu}/{parentKey}/create",
     *     name="oro_navigation_menu_update_create",
     *     defaults={"parentKey" = null}
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
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');

        $menuUpdate = $manager->createMenuUpdate();

        $parent = $manager->getMenuUpdateByKey($menu, $parentKey);
        if (!$parent) {
            throw $this->createNotFoundException();
        }

        $menuUpdate->setParentKey($parent->getKey());
        $menuUpdate->setMenu($menu);

        return $this->update($menuUpdate);
    }

    /**
     * @Route(
     *     "/{menu}/{parentKey}/update/{key}",
     *     name="oro_navigation_menu_update_update",
     *     defaults={"parentKey" = null}
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
     * @param string $parentKey
     * @param string $key
     * @return array|RedirectResponse
     */
    public function updateAction($menu, $parentKey, $key)
    {
        /** @var MenuUpdateManager $manager */
        $manager = $this->get('oro_navigation.manager.menu_update_default');
        
        $parent = $manager->getMenuUpdateByKey($menu, $parentKey);
        if (!$parent) {
            throw $this->createNotFoundException();
        }

        $menuUpdate = $manager->getMenuUpdateByKey($menu, $key);
        if (!$menuUpdate) {
            throw $this->createNotFoundException();
        }

        return $this->update($menuUpdate);
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
        return [];
    }

    /**
     * @param MenuUpdateInterface $menuUpdate
     * @return array|RedirectResponse
     */
    private function update(MenuUpdateInterface $menuUpdate)
    {
        $form = $this->createForm(MenuUpdateType::NAME, $menuUpdate);

        return $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );
    }
}
