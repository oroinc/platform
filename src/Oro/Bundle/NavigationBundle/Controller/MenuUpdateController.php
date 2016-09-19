<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Form\Type\MenuUpdateType;

/**
 * @Route("/menuupdate")
 */
class MenuUpdateController extends Controller
{
    /**
     * @Route("/{menu}/create/{parentKey}", name="oro_navigation_menu_update_create", defaults={"parentKey" = null})
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
        $menuUpdate = $this->createMenuUpdate($menu, null, $parentKey);

        return $this->update($menuUpdate, ['validation_groups' => ['Create']]);
    }

    /**
     * @Route("/{menu}/update/{key}/{parentKey}", name="oro_navigation_menu_update_update", defaults={"parentKey" = null})
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
        $menuUpdate = $this->getDoctrine()
            ->getManagerForClass('OroNavigationBundle:MenuUpdate')
            ->getRepository('OroNavigationBundle:MenuUpdate')
            ->getMenuUpdateByMenuAndKey($menu, $key);
        if ($menuUpdate !== null) {
            return $this->update($menuUpdate);
        }

        $menuUpdate = $this->createMenuUpdate($menu, $key, $parentKey);

        return $this->update($menuUpdate, ['validation_groups' => ['Create']]);
    }

    /**
     * @param MenuUpdate $menuUpdate
     * @param array $formOptions
     * @return array|RedirectResponse
     */
    private function update(MenuUpdate $menuUpdate, $formOptions = [])
    {
        $form = $this->createForm(
            MenuUpdateType::NAME,
            $menuUpdate,
            array_merge_recursive(['validation_groups' => ['Default']], $formOptions)
        );

        return $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );
    }

    /**
     * @param string $menu
     * @param string $key
     * @param string $parentKey
     * @return MenuUpdate
     */
    private function createMenuUpdate($menu, $key, $parentKey)
    {
        $menuUpdate = new MenuUpdate();
        $menuUpdate
            ->setOwnershipType(MenuUpdate::OWNERSHIP_GLOBAL)
            ->setMenu($menu)
            ->setKey($key)
            ->setParentKey($parentKey)
        ;

        return $menuUpdate;
    }

    /**
     * @Route("/", name="oro_navigation_menu_update_index")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/{menu}", name="oro_navigation_menu_update_view")
     * @Template()
     *
     * @param string $menu
     *
     * @return array
     */
    public function viewAction($menu)
    {
        return [
            'entity' => $this->get('oro_menu.builder_chain')->get($menu)
        ];
    }
}
