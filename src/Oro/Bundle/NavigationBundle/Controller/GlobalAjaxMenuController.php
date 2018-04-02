<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/menu/global")
 */
class GlobalAjaxMenuController extends AbstractAjaxMenuController
{
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
     * @Route("/reset/{menuName}", name="oro_navigation_global_menu_ajax_reset")
     * @Method({"DELETE"})
     *
     * {@inheritdoc}
     */
    public function resetAction($menuName, Request $request)
    {
        return parent::resetAction($menuName, $request);
    }

    /**
     * @Route("/create/{menuName}/{parentKey}", name="oro_navigation_global_menu_ajax_create")
     * @Method({"POST"})
     *
     * {@inheritdoc}
     */
    public function createAction(Request $request, $menuName, $parentKey)
    {
        return parent::createAction($request, $menuName, $parentKey);
    }

    /**
     * @Route("/delete/{menuName}/{key}", name="oro_navigation_global_menu_ajax_delete")
     * @Method({"DELETE"})
     *
     * {@inheritdoc}
     */
    public function deleteAction($menuName, $key, Request $request)
    {
        return parent::deleteAction($menuName, $key, $request);
    }

    /**
     * @Route("/show/{menuName}/{key}", name="oro_navigation_global_menu_ajax_show")
     * @Method({"PUT"})
     *
     * {@inheritdoc}
     */
    public function showAction($menuName, $key, Request $request)
    {
        return parent::showAction($menuName, $key, $request);
    }

    /**
     * @Route("/hide/{menuName}/{key}", name="oro_navigation_global_menu_ajax_hide")
     * @Method({"PUT"})
     *
     * {@inheritdoc}
     */
    public function hideAction($menuName, $key, Request $request)
    {
        return parent::hideAction($menuName, $key, $request);
    }

    /**
     * @Route("/move/{menuName}", name="oro_navigation_global_menu_ajax_move")
     * @Method({"PUT"})
     *
     * {@inheritdoc}
     */
    public function moveAction(Request $request, $menuName)
    {
        return parent::moveAction($request, $menuName);
    }
}
