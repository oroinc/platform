<?php

namespace Oro\Bundle\EntityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for entity field metadata.
 */
class EntityFieldController extends AbstractFOSRestController
{
    /**
     * Get entity fields.
     *
     * @param Request $request
     * @param string $entityName Entity full class name; backslashes (\) should be replaced with underscore (_).
     *
     * @QueryParam(
     *      name="with-relations", requirements="(1)|(0)", nullable=true, strict=true, default="0",
     *      description="Indicates whether association fields should be returned as well.")
     * @QueryParam(
     *      name="with-virtual-fields", requirements="(1)|(0)", nullable=true, strict=true, default="0",
     *      description="Indicates whether virtual fields should be returned as well.")
     * @QueryParam(
     *      name="with-entity-details", requirements="(1)|(0)", nullable=true, strict=true, default="0",
     *      description="Indicates whether details of related entity should be returned as well.")
     * @QueryParam(
     *      name="with-unidirectional", requirements="(1)|(0)", nullable=true, strict=true, default="0",
     *      description="Indicates whether Unidirectional association fields should be returned.")
     * @QueryParam(
     *      name="apply-exclusions", requirements="(1)|(0)", nullable=true, strict=true, default="1",
     *      description="Indicates whether exclusion logic should be applied.")
     * @ApiDoc(
     *      description="Get entity fields",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getFieldsAction(Request $request, $entityName)
    {
        $entityName = $this->get('oro_entity.routing_helper')->resolveEntityClass($entityName);
        $withRelations = filter_var($request->get('with-relations'), FILTER_VALIDATE_BOOLEAN);
        $withEntityDetails = filter_var($request->get('with-entity-details'), FILTER_VALIDATE_BOOLEAN);
        $withUnidirectional = filter_var($request->get('with-unidirectional'), FILTER_VALIDATE_BOOLEAN);
        $withVirtualFields = filter_var($request->get('with-virtual-fields'), FILTER_VALIDATE_BOOLEAN);
        $applyExclusions = filter_var($request->get('apply-exclusions'), FILTER_VALIDATE_BOOLEAN);

        /** @var EntityFieldProvider $provider */
        $provider = $this->get('oro_entity.entity_field_provider');

        $statusCode = Response::HTTP_OK;
        $options = EntityFieldProvider::OPTION_TRANSLATE;
        $options |= $withRelations ? EntityFieldProvider::OPTION_WITH_RELATIONS : 0;
        $options |= $withVirtualFields ? EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS : 0;
        $options |= $withEntityDetails ? EntityFieldProvider::OPTION_WITH_ENTITY_DETAILS : 0;
        $options |= $withUnidirectional ? EntityFieldProvider::OPTION_WITH_UNIDIRECTIONAL : 0;
        $options |= $applyExclusions ? EntityFieldProvider::OPTION_APPLY_EXCLUSIONS : 0;
        try {
            $result = $provider->getEntityFields($entityName, $options);
        } catch (InvalidEntityException $ex) {
            $statusCode = Response::HTTP_NOT_FOUND;
            $result = ['message' => $ex->getMessage()];
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
