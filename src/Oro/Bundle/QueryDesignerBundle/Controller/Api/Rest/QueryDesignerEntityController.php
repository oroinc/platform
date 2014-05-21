<?php

namespace Oro\Bundle\QueryDesignerBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\Rest\Util\Codes;


use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;

/**
 * @RouteResource("querydesigner/entity")
 * @NamePrefix("oro_api_querydesigner_")
 */
class QueryDesignerEntityController extends FOSRestController implements ClassResourceInterface
{
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
     *      name="with-unidirectional", requirements="(1)|(0)",
     *      description="Indicates whether Unidirectional association fields should be returned.")
     * @QueryParam(
     *      name="query-type", requirements="([\w+])",
     *      description="Query type, e.g. report, segment, etc.")
     * @ApiDoc(
     *      description="Get entities with fields",
     *      resource=true
     * )
     * @Get(name="oro_api_querydesigner_fields_entity")
     *
     * @return Response
     */
    public function fieldsAction()
    {
        $withRelations      = ('1' == $this->getRequest()->query->get('with-relations'));
        $withUnidirectional = ('1' == $this->getRequest()->query->get('with-unidirectional'));
        $withVirtualFields  = ('1' == $this->getRequest()->query->get('with-virtual-fields'));
        $queryType          = $this->getRequest()->query->get('query-type');

        // set query type for exclude-related logic
        $this->get('oro_query_designer.entity_field_provider')
            ->setQueryType($queryType);

        /** @var EntityWithFieldsProvider $provider */
        $provider = $this->get('oro_query_designer.entity_field_list_provider');

        $statusCode = Codes::HTTP_OK;
        try {
            $result = $provider->getFields(
                $withVirtualFields,
                $withUnidirectional,
                $withRelations,
                $queryType
            );
        } catch (InvalidEntityException $ex) {
            $statusCode = Codes::HTTP_NOT_FOUND;
            $result     = array('message' => $ex->getMessage());
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
