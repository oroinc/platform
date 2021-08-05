<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for countries.
 */
class CountryController extends AbstractFOSRestController
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
        $items = $this->getDoctrine()->getRepository('OroAddressBundle:Country')->findAll();

        return $this->handleView(
            $this->view($items, is_array($items) ? Response::HTTP_OK : Response::HTTP_NOT_FOUND)
        );
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
        $item = $this->getDoctrine()->getRepository('OroAddressBundle:Country')->find($id);

        return $this->handleView(
            $this->view($item, is_object($item) ? Response::HTTP_OK : Response::HTTP_NOT_FOUND)
        );
    }
}
