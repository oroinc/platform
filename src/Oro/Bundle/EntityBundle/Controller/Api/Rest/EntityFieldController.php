<?php

namespace Oro\Bundle\EntityBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;

/**
 * @RouteResource("entity")
 * @NamePrefix("oro_api_")
 */
class EntityFieldController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get entity fields.
     *
     * @param string $entityName Entity full class name; backslashes (\) should be replaced with underscore (_).
     *
     * @Get(requirements={"entityName"="((\w+)_)+(\w+)"})
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
    public function getFieldsAction($entityName)
    {
        $entityName = $this->get('oro_entity.routing_helper')->resolveEntityClass($entityName);
        $withRelations = filter_var($this->getRequest()->get('with-relations'), FILTER_VALIDATE_BOOLEAN);
        $withEntityDetails = filter_var($this->getRequest()->get('with-entity-details'), FILTER_VALIDATE_BOOLEAN);
        $withUnidirectional = filter_var($this->getRequest()->get('with-unidirectional'), FILTER_VALIDATE_BOOLEAN);
        $withVirtualFields = filter_var($this->getRequest()->get('with-virtual-fields'), FILTER_VALIDATE_BOOLEAN);
        $applyExclusions = filter_var($this->getRequest()->get('apply-exclusions'), FILTER_VALIDATE_BOOLEAN);

        /** @var EntityFieldProvider $provider */
        $provider = $this->get('oro_entity.entity_field_provider');

        $statusCode = Codes::HTTP_OK;
        try {
            $result = $provider->getFields(
                $entityName,
                $withRelations,
                $withVirtualFields,
                $withEntityDetails,
                $withUnidirectional,
                $applyExclusions
            );
        } catch (InvalidEntityException $ex) {
            $statusCode = Codes::HTTP_NOT_FOUND;
            $result = ['message' => $ex->getMessage()];
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
