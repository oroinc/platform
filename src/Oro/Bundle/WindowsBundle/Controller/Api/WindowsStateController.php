<?php

namespace Oro\Bundle\WindowsBundle\Controller\Api;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\WindowsBundle\Entity\AbstractWindowsState;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * REST API controller for windows state.
 */
class WindowsStateController extends AbstractFOSRestController
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
        if (null === $manager) {
            return $this->handleNotFound();
        }

        $items = $manager->getWindowsStates();
        if (!$items) {
            return $this->handleNotFound();
        }

        $serializedItems = [];
        foreach ($items as $item) {
            $serializedItems[] = $this->serializeWindowsState($item);
        }

        return $this->handleView($this->view($serializedItems, Response::HTTP_OK));
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
            return $this->handleNotFound();
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
            return $this->handleNotFound();
        }

        try {
            if (!$manager->updateWindowsState($windowId)) {
                return $this->handleNotFound();
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
            return $this->handleNotFound();
        }

        try {
            if (!$manager->deleteWindowsState($windowId)) {
                return $this->handleNotFound();
            }
        } catch (\InvalidArgumentException $e) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Wrong JSON inside POST body');
        }

        return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
    }

    protected function serializeWindowsState(AbstractWindowsState $windowsState): array
    {
        return [
            'id'                    => $windowsState->getId(),
            'data'                  => $windowsState->getData(),
            'rendered_successfully' => $windowsState->isRenderedSuccessfully(),
            'created_at'            => $windowsState->getCreatedAt(),
            'updated_at'            => $windowsState->getUpdatedAt()
        ];
    }

    private function getWindowsStateManager(): ?WindowsStateManager
    {
        return $this->get('oro_windows.manager.windows_state_registry')->getManager();
    }

    private function handleNotFound(): Response
    {
        return $this->handleView($this->view([], Response::HTTP_NOT_FOUND));
    }
}
