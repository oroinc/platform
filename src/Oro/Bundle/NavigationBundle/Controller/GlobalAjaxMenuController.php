<?php

namespace Oro\Bundle\NavigationBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GlobalAjaxMenuController extends AbstractAjaxMenuController
{
    /**
     * @Route("/menu/reset/{menuName}", name="oro_navigation_menuupdate_reset")
     * @Method("DELETE")
     *
     * @param string  $menuName
     * @param Request $request
     *
     * @return Response
     */
    public function resetAction($menuName, Request $request)
    {

    }

    /**
     * @Route("/menu/create/{menuName}/{parentKey}", name="oro_navigation_menuupdate_create")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $menuName
     * @param string  $parentKey
     *
     * @return Response
     */
    public function createAction(Request $request, $menuName, $parentKey)
    {

    }

    /**
     * @Route("/menu/delete/{menuName}/{key}", name="oro_navigation_menuupdate_delete")
     * @Method("DELETE")
     *
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction($menuName, $key, Request $request)
    {

    }

    /**
     * @Route("/menu/show/{menuName}/{key}", name="oro_navigation_menuupdate_show")
     * @Method("PUT")
     *
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return Response
     */
    public function showAction($menuName, $key, Request $request)
    {
    }

    /**
     * @Route("/menu/hide/{menuName}/{key}", name="oro_navigation_menuupdate_hide")
     * @Method("PUT")
     *
     * @param string  $menuName
     * @param string  $key
     * @param Request $request
     *
     * @return Response
     */
    public function hideAction($menuName, $key, Request $request)
    {
    }

    /**
     * @Route("/menu/move/{menuName}", name="oro_navigation_menuupdate_move")
     * @Method("PUT")
     *
     * @param Request $request
     * @param string  $menuName
     *
     * @return Response
     */
    public function moveAction(Request $request, $menuName)
    {
    }
}
