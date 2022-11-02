<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for address types.
 */
class AddressTypeController extends RestGetController
{
    /**
     * REST GET list
     *
     * @ApiDoc(
     *      description="Get all address types items",
     *      resource=true
     * )
     * @return Response
     */
    public function cgetAction()
    {
        return $this->handleGetListRequest(1, PHP_INT_MAX);
    }

    /**
     * REST GET item
     *
     * @param string $name
     *
     * @ApiDoc(
     *      description="Get address type item",
     *      resource=true
     * )
     * @return Response
     */
    public function getAction($name)
    {
        return $this->handleGetRequest($name);
    }


    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_address.api.manager.address_type');
    }
}
