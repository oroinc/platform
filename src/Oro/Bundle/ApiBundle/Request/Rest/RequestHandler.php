<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles REST API requests.
 */
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
        switch ($request->getMethod()) {
            case Request::METHOD_GET:
                return $this->actionHandler->handleGet($request);
            case Request::METHOD_PATCH:
                return $this->actionHandler->handleUpdate($request);
            case Request::METHOD_DELETE:
                return $this->actionHandler->handleDelete($request);
            case Request::METHOD_OPTIONS:
                return $this->actionHandler->handleOptionsItem($request);
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
        switch ($request->getMethod()) {
            case Request::METHOD_GET:
                return $this->actionHandler->handleGetList($request);
            case Request::METHOD_POST:
                return $this->actionHandler->handleCreate($request);
            case Request::METHOD_DELETE:
                return $this->actionHandler->handleDeleteList($request);
            case Request::METHOD_OPTIONS:
                return $this->actionHandler->handleOptionsList($request);
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
        switch ($request->getMethod()) {
            case Request::METHOD_GET:
                return $this->actionHandler->handleGetSubresource($request);
            case Request::METHOD_PATCH:
                return $this->actionHandler->handleUpdateSubresource($request);
            case Request::METHOD_POST:
                return $this->actionHandler->handleAddSubresource($request);
            case Request::METHOD_DELETE:
                return $this->actionHandler->handleDeleteSubresource($request);
            case Request::METHOD_OPTIONS:
                return $this->actionHandler->handleOptionsSubresource($request);
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
        switch ($request->getMethod()) {
            case Request::METHOD_GET:
                return $this->actionHandler->handleGetRelationship($request);
            case Request::METHOD_PATCH:
                return $this->actionHandler->handleUpdateRelationship($request);
            case Request::METHOD_POST:
                return $this->actionHandler->handleAddRelationship($request);
            case Request::METHOD_DELETE:
                return $this->actionHandler->handleDeleteRelationship($request);
            case Request::METHOD_OPTIONS:
                return $this->actionHandler->handleOptionsRelationship($request);
        }

        return $this->actionHandler->handleNotAllowedRelationship($request);
    }

    /**
     * Handles "/api/{entity}" requests for single item API resources that do not have an identifier.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleItemWithoutId(Request $request): Response
    {
        switch ($request->getMethod()) {
            case Request::METHOD_GET:
                return $this->actionHandler->handleGet($request);
            case Request::METHOD_POST:
                return $this->actionHandler->handleCreate($request);
            case Request::METHOD_PATCH:
                return $this->actionHandler->handleUpdate($request);
            case Request::METHOD_DELETE:
                return $this->actionHandler->handleDelete($request);
            case Request::METHOD_OPTIONS:
                return $this->actionHandler->handleOptionsItem($request);
        }

        return $this->actionHandler->handleNotAllowedItem($request);
    }
}
