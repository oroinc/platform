<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestHandler
{
    /** @var RequestActionHandler */
    private $actionHandler;

    /**
     * @param RequestActionHandler $actionHandler
     */
    public function __construct(RequestActionHandler $actionHandler)
    {
        $this->actionHandler = $actionHandler;
    }

    /**
     * Handles "/api/{entity}/{id}" requests.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleItem(Request $request): Response
    {
        $method = $request->getMethod();
        if ('GET' === $method) {
            return $this->actionHandler->handleGet($request);
        }
        if ('PATCH' === $method) {
            return $this->actionHandler->handleUpdate($request);
        }
        if ('DELETE' === $method) {
            return $this->actionHandler->handleDelete($request);
        }

        return $this->actionHandler->handleNotAllowedItem($request);
    }

    /**
     * Handles "/api/{entity}" requests.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleList(Request $request): Response
    {
        $method = $request->getMethod();
        if ('GET' === $method) {
            return $this->actionHandler->handleGetList($request);
        }
        if ('POST' === $method) {
            return $this->actionHandler->handleCreate($request);
        }
        if ('DELETE' === $method) {
            return $this->actionHandler->handleDeleteList($request);
        }

        return $this->actionHandler->handleNotAllowedList($request);
    }

    /**
     * Handles "/api/{entity}/{id}/{association}" requests.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleSubresource(Request $request): Response
    {
        $method = $request->getMethod();
        if ('GET' === $method) {
            return $this->actionHandler->handleGetSubresource($request);
        }

        return $this->actionHandler->handleNotAllowedSubresource($request);
    }

    /**
     * Handles "/api/{entity}/{id}/relationships/{association}" requests.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleRelationship(Request $request): Response
    {
        $method = $request->getMethod();
        if ('GET' === $method) {
            return $this->actionHandler->handleGetRelationship($request);
        }
        if ('PATCH' === $method) {
            return $this->actionHandler->handleUpdateRelationship($request);
        }
        if ('POST' === $method) {
            return $this->actionHandler->handleAddRelationship($request);
        }
        if ('DELETE' === $method) {
            return $this->actionHandler->handleDeleteRelationship($request);
        }

        return $this->actionHandler->handleNotAllowedRelationship($request);
    }
}
