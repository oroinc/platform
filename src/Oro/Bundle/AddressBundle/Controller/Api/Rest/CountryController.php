<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("country")
 * @NamePrefix("oro_api_")
 */
class CountryController extends FOSRestController
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
