<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for countries.
 */
class CountryController extends RestGetController
{
    /**
     * Get countries
     *
     * @ApiDoc(
     *      description="Get countries",
     *      resource=true
     * )
     * @return Response
     */
    public function cgetAction()
    {
        return $this->handleGetListRequest(1, PHP_INT_MAX);
    }

    /**
     * REST GET country
     *
     * @param string $id
     *
     * @ApiDoc(
     *      description="Get country",
     *      resource=true
     * )
     * @return Response
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_address.api.manager.country');
    }
}
