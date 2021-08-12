<?php

namespace Oro\Bundle\EntityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for entity metadata.
 */
class EntityController extends AbstractFOSRestController
{
    /**
     * Get entities.
     *
     * @QueryParam(
     *      name="apply-exclusions", requirements="(1)|(0)", nullable=true, strict=true, default="1",
     *      description="Indicates whether exclusion logic should be applied.")
     *
     * @ApiDoc(
     *      description="Get entities",
     *      resource=true
     * )
     * @param Request $request
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $applyExclusions = filter_var($request->get('apply-exclusions'), FILTER_VALIDATE_BOOLEAN);

        /** @var EntityProvider $provider */
        $provider = $this->get('oro_entity.entity_provider');
        $result = $provider->getEntities(false, $applyExclusions);

        return $this->handleView($this->view($result, Response::HTTP_OK));
    }

    /**
     * Get entities with fields
     *
     * @QueryParam(
     *      name="with-virtual-fields", requirements="(1)|(0)", nullable=true, strict=true, default="0",
     *      description="Indicates whether virtual fields should be returned as well.")
     * @QueryParam(
     *      name="with-relations", requirements="(1)|(0)", nullable=true, strict=true, default="0",
     *      description="Indicates whether association fields should be returned as well.")
     * @QueryParam(
     *      name="with-unidirectional", requirements="(1)|(0)", nullable=true, strict=true, default="0",
     *      description="Indicates whether Unidirectional association fields should be returned.")
     * @QueryParam(
     *      name="apply-exclusions", requirements="(1)|(0)", nullable=true, strict=true, default="1",
     *      description="Indicates whether exclusion logic should be applied.")
     * @ApiDoc(
     *      description="Get entities with fields",
     *      resource=true
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function fieldsAction(Request $request)
    {
        $withRelations      = filter_var($request->get('with-relations'), FILTER_VALIDATE_BOOLEAN);
        $withUnidirectional = filter_var($request->get('with-unidirectional'), FILTER_VALIDATE_BOOLEAN);
        $withVirtualFields  = filter_var($request->get('with-virtual-fields'), FILTER_VALIDATE_BOOLEAN);
        $applyExclusions    = filter_var($request->get('apply-exclusions'), FILTER_VALIDATE_BOOLEAN);

        /** @var EntityWithFieldsProvider $provider */
        $provider = $this->get('oro_entity.entity_field_list_provider');

        $statusCode = Response::HTTP_OK;
        try {
            $result = $provider->getFields(
                $withVirtualFields,
                $withUnidirectional,
                $withRelations,
                $applyExclusions
            );
        } catch (InvalidEntityException $ex) {
            $statusCode = Response::HTTP_NOT_FOUND;
            $result     = array('message' => $ex->getMessage());
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
