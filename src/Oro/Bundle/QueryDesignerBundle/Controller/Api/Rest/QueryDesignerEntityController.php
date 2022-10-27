<?php

namespace Oro\Bundle\QueryDesignerBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for the query designer.
 */
class QueryDesignerEntityController extends AbstractFOSRestController
{
    /**
     * Get entities with fields
     *
     * @QueryParam(
     *      name="with-relations",
     *      nullable=true,
     *      requirements="true|false",
     *      default="true",
     *      strict=true,
     *      description="Indicates whether association fields should be returned as well."
     * )
     *
     * @ApiDoc(
     *      description="Get entities with fields",
     *      resource=true
     * )
     * @param Request $request
     * @return Response
     */
    public function fieldsAction(Request $request)
    {
        /** @var EntityWithFieldsProvider $provider */
        $provider = $this->get('oro_query_designer.entity_field_list_provider');
        $withRelations = filter_var($request->get('with-relations', true), FILTER_VALIDATE_BOOLEAN);
        $statusCode = Response::HTTP_OK;

        try {
            $result = $provider->getFields(true, true, $withRelations);
        } catch (InvalidEntityException $ex) {
            $statusCode = Response::HTTP_NOT_FOUND;
            $result = ['message' => $ex->getMessage()];
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
