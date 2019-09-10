<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\Request\Rest\RequestHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller.
 */
class RestApiController
{
    /** @var RequestHandler */
    private $requestHandler;

    /**
     * @param RequestHandler $requestHandler
     */
    public function __construct(RequestHandler $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    /**
     * Handle an entity
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     description="Handle an entity",
     *     resource=true,
     *     views={"rest_plain", "rest_json_api"}
     * )
     *
     * @return Response
     */
    public function itemAction(Request $request): Response
    {
        return $this->requestHandler->handleItem($request);
    }

    /**
     * Handle a list of entities
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     description="Handle a list of entities",
     *     resource=true,
     *     views={"rest_plain", "rest_json_api"}
     * )
     *
     * @return Response
     */
    public function listAction(Request $request): Response
    {
        return $this->requestHandler->handleList($request);
    }

    /**
     * Handle a subresource
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     description="Handle a subresource",
     *     resource=true,
     *     views={"rest_plain", "rest_json_api"}
     * )
     *
     * @return Response
     */
    public function subresourceAction(Request $request): Response
    {
        return $this->requestHandler->handleSubresource($request);
    }

    /**
     * Handle a relationship
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     description="Handle a relationship",
     *     resource=true,
     *     views={"rest_plain", "rest_json_api"}
     * )
     *
     * @return Response
     */
    public function relationshipAction(Request $request): Response
    {
        return $this->requestHandler->handleRelationship($request);
    }

    /**
     * Handle an entity without identifier
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     description="Handle an entity without identifier",
     *     resource=true,
     *     views={"rest_plain", "rest_json_api"}
     * )
     *
     * @return Response
     */
    public function itemWithoutIdAction(Request $request): Response
    {
        return $this->requestHandler->handleItemWithoutId($request);
    }
}
