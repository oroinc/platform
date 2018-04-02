<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\Request\Rest\RequestHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller.
 */
class RestApiController extends Controller
{
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
        return $this->getHandler()->handleItem($request);
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
        return $this->getHandler()->handleList($request);
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
        return $this->getHandler()->handleSubresource($request);
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
        return $this->getHandler()->handleRelationship($request);
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
        return $this->getHandler()->handleItemWithoutId($request);
    }

    /**
     * @return RequestHandler
     */
    private function getHandler(): RequestHandler
    {
        return $this->get('oro_api.rest.request_handler');
    }
}
