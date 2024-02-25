<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Menu controller for global level.
 */
#[Route(path: '/menu/global')]
class GlobalMenuController extends AbstractMenuController
{
    /**
     *
     * @return array
     */
    #[Route(path: '/', name: 'oro_navigation_global_menu_index')]
    #[Template]
    public function indexAction()
    {
        return $this->index();
    }

    /**
     *
     * @param string $menuName
     *
     * @return array
     */
    #[Route(path: '/{menuName}', name: 'oro_navigation_global_menu_view')]
    #[Template]
    public function viewAction($menuName)
    {
        return $this->view($menuName);
    }

    /**
     *
     * @param string $menuName
     * @param string|null $parentKey
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/{menuName}/create/{parentKey}', name: 'oro_navigation_global_menu_create')]
    #[Template('@OroNavigation/GlobalMenu/update.html.twig')]
    public function createAction($menuName, $parentKey = null)
    {
        return parent::create($menuName, $parentKey);
    }

    /**
     *
     * @param string $menuName
     * @param string $key
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/{menuName}/update/{key}', name: 'oro_navigation_global_menu_update')]
    #[Template]
    public function updateAction($menuName, $key)
    {
        return parent::update($menuName, $key);
    }

    /**
     *
     * @param Request $request
     * @param string $menuName
     * @return array|RedirectResponse
     */
    #[Route(path: '/{menuName}/move', name: 'oro_navigation_global_menu_move')]
    public function moveAction(Request $request, $menuName)
    {
        return parent::move($request, $menuName);
    }

    /**
     * {@inheritDoc}
     */
    protected function checkAcl(array $context)
    {
        if (!$this->isGranted('oro_config_system')) {
            throw $this->createAccessDeniedException();
        }
        parent::checkAcl($context);
    }

    /**
     * {@inheritDoc}
     */
    protected function handleUpdate(
        MenuUpdateInterface $menuUpdate,
        array $context,
        ItemInterface $menu
    ): array|RedirectResponse {
        $response = parent::handleUpdate($menuUpdate, $context, $menu);

        // On save RedirectResponse is returned, during rendering response is an array.
        // Perform updates only after update.
        if (!is_array($response)) {
            $this->updateDependentMenuUpdateUrls($menuUpdate);
        }

        return $response;
    }
}
