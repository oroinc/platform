<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for regions.
 */
class RegionController extends RestGetController
{
    /**
     * REST GET region by id
     *
     * @ApiDoc(
     *     description="Get region by id",
     *     resource=true,
     *     requirements={
     *         {"name"="id", "dataType"="string", "requirement"=".+", "description"="region combined code"}
     *     }
     * )
     * @param string $id
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
        return $this->get('oro_address.api.manager.region');
    }
}
