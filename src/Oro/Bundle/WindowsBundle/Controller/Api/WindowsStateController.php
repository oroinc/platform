<?php

namespace Oro\Bundle\WindowsBundle\Controller\Api;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @RouteResource("windows")
 * @NamePrefix("oro_api_")
 */
class WindowsStateController extends FOSRestController
{
    /**
     * REST GET list
     *
     * @ApiDoc(
     *  description="Get all Windows States for user",
     *  resource=true
     * )
     * @return Response
     */
    public function cgetAction()
    {
        $items = $this->getWindowsStatesManager()->getWindowsStates();

        return $this->handleView(
            $this->view($items, $items ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND)
        );
    }

    /**
     * REST POST
     *
     * @ApiDoc(
     *  description="Add Windows State",
     *  resource=true
     * )
     * @return Response
     */
    public function postAction()
    {
        try {
            $id = $this->getWindowsStatesManager()->createWindowsState();
        } catch (\InvalidArgumentException $e) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, 'Wrong JSON inside POST body');
        }

        return $this->handleView(
            $this->view(['id' => $id], Codes::HTTP_CREATED)
        );
    }

    /**
     * REST PUT
     *
     * @param int $windowId Window state id
     * @return Response
     * @ApiDoc(
     *  description="Update Windows state item",
     *  resource=true
     * )
     */
    public function putAction($windowId)
    {
        try {
            if (!$this->getWindowsStatesManager()->updateWindowsState($windowId)) {
                return $this->handleView($this->view([], Codes::HTTP_NOT_FOUND));
            }
        } catch (\InvalidArgumentException $e) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, 'Wrong JSON inside POST body');
        }

        return $this->handleView($this->view([], Codes::HTTP_OK));
    }

    /**
     * REST DELETE
     *
     * @param int $windowId
     *
     * @ApiDoc(
     *  description="Remove Windows state",
     *  resource=true
     * )
     * @return Response
     */
    public function deleteAction($windowId)
    {
        try {
            if (!$this->getWindowsStatesManager()->deleteWindowsState($windowId)) {
                return $this->handleView($this->view([], Codes::HTTP_NOT_FOUND));
            }
        } catch (\InvalidArgumentException $e) {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, 'Wrong JSON inside POST body');
        }

        return $this->handleView($this->view([], Codes::HTTP_NO_CONTENT));
    }

    /**
     * @return WindowsStateManager
     */
    protected function getWindowsStatesManager()
    {
        return $this->get('oro_windows.manager.windows_state');
    }
}
