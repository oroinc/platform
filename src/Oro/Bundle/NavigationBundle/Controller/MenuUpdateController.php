<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     *     requirements={"menu" = "[-\w]+", "parentKey" = "[-\w]+"}
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
        if ($parentKey) {
            $parentUpdate = $this->getDoctrine()
                ->getManagerForClass('OroNavigationBundle:MenuUpdate')
                ->getRepository('OroNavigationBundle:MenuUpdate')
                ->getMenuUpdateByMenuAndKey($menu, $parentKey);

            if (!$parentUpdate) {
                throw new NotFoundHttpException();
            }
        }

        $menuUpdate = $this->createMenuUpdate($menu, null, $parentKey);

        return $this->update($menuUpdate);
    }

    /**
     * @Route(
     *     "/{menu}/update/{key}/{parentKey}",
     *     name="oro_navigation_menu_update_update",
     *     defaults={"parentKey" = null},
     *     requirements={"menu" = "[-\w]+", "key" = "[-\w]+", "parentKey" = "[-\w]+"}
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
        if ($parentKey) {
            $parentUpdate = $this->getDoctrine()
                ->getManagerForClass('OroNavigationBundle:MenuUpdate')
                ->getRepository('OroNavigationBundle:MenuUpdate')
                ->getMenuUpdateByMenuAndKey($menu, $parentKey);

            if (!$parentUpdate) {
                throw new NotFoundHttpException();
            }
        }

        $menuUpdate = $this->getDoctrine()
            ->getManagerForClass('OroNavigationBundle:MenuUpdate')
            ->getRepository('OroNavigationBundle:MenuUpdate')
            ->getMenuUpdateByMenuAndKey($menu, $key);

        if ($menuUpdate !== null) {
            $menuUpdate->setParentKey($parentKey);

            return $this->update($menuUpdate);
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @param MenuUpdate $menuUpdate
     * @return array|RedirectResponse
     */
    private function update(MenuUpdate $menuUpdate)
    {
        $form = $this->createForm(MenuUpdateType::NAME, $menuUpdate);

        return $this->get('oro_form.model.update_handler')->update(
            $menuUpdate,
            $form,
            $this->get('translator')->trans('oro.navigation.menuupdate.saved_message')
        );
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
            ->setParentKey($parentKey);
        ;

        return $menuUpdate;
    }
}
