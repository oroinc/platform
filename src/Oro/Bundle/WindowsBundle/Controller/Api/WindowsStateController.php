<?php

namespace Oro\Bundle\WindowsBundle\Controller\Api;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The controller for windows state API.
 *
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
        $manager = $this->getWindowsStateManager();
        if (null !== $manager) {
            $items = $manager->getWindowsStates();
            if ($items) {
                return $this->handleView($this->view($items, Response::HTTP_OK));
            }
        }

        return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
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
        $manager = $this->getWindowsStateManager();
        if (null === $manager) {
            return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
        }

        try {
            $id = $manager->createWindowsState();
        } catch (\InvalidArgumentException $e) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Wrong JSON inside POST body');
        }

        return $this->handleView($this->view(['id' => $id], Response::HTTP_CREATED));
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
        $manager = $this->getWindowsStateManager();
        if (null === $manager) {
            return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
        }

        try {
            if (!$manager->updateWindowsState($windowId)) {
                return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
            }
        } catch (\InvalidArgumentException $e) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Wrong JSON inside POST body');
        }

        return $this->handleView($this->view([], Response::HTTP_OK));
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
        $manager = $this->getWindowsStateManager();
        if (null === $manager) {
            return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
        }

        try {
            if (!$manager->deleteWindowsState($windowId)) {
                return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
            }
        } catch (\InvalidArgumentException $e) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Wrong JSON inside POST body');
        }

        return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
    }

    private function getWindowsStateManager(): ?WindowsStateManager
    {
        return $this->get('oro_windows.manager.windows_state_registry')->getManager();
    }
}
