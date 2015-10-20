<?php

namespace Oro\Bundle\EntityBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations as Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;

/**
 * @RouteResource("entity")
 * @NamePrefix("oro_api_")
 */
class EntityController extends FOSRestController implements ClassResourceInterface
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
     *
     * @return Response
     */
    public function cgetAction()
    {
        $applyExclusions = filter_var($this->getRequest()->get('apply-exclusions'), FILTER_VALIDATE_BOOLEAN);

        /** @var EntityProvider $provider */
        $provider = $this->get('oro_entity.entity_provider');
        $result = $provider->getEntities(false, $applyExclusions);

        return $this->handleView($this->view($result, Codes::HTTP_OK));
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
     * @return Response
     */
    public function fieldsAction()
    {
        $withRelations      = filter_var($this->getRequest()->get('with-relations'), FILTER_VALIDATE_BOOLEAN);
        $withUnidirectional = filter_var($this->getRequest()->get('with-unidirectional'), FILTER_VALIDATE_BOOLEAN);
        $withVirtualFields  = filter_var($this->getRequest()->get('with-virtual-fields'), FILTER_VALIDATE_BOOLEAN);
        $applyExclusions    = filter_var($this->getRequest()->get('apply-exclusions'), FILTER_VALIDATE_BOOLEAN);

        /** @var EntityWithFieldsProvider $provider */
        $provider = $this->get('oro_entity.entity_field_list_provider');

        $statusCode = Codes::HTTP_OK;
        try {
            $result = $provider->getFields(
                $withVirtualFields,
                $withUnidirectional,
                $withRelations,
                $applyExclusions
            );
        } catch (InvalidEntityException $ex) {
            $statusCode = Codes::HTTP_NOT_FOUND;
            $result     = array('message' => $ex->getMessage());
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
