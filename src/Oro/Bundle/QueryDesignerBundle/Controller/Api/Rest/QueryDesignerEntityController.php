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
        /** @var EntityWithFieldsProvider $provider */
        $provider = $this->get('oro_query_designer.entity_field_list_provider');

        $statusCode = Codes::HTTP_OK;
        try {
            $result = $provider->getFields(true, true);
        } catch (InvalidEntityException $ex) {
            $statusCode = Codes::HTTP_NOT_FOUND;
            $result     = array('message' => $ex->getMessage());
        }

        return $this->handleView($this->view($result, $statusCode));
    }
}
