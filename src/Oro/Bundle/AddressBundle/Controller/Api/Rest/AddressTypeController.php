<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for address types.
 */
class AddressTypeController extends AbstractFOSRestController
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
        $items = $this->getDoctrine()->getRepository('OroAddressBundle:AddressType')->findAll();

        return $this->handleView(
            $this->view($items, is_array($items) ? Response::HTTP_OK : Response::HTTP_NOT_FOUND)
        );
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
        if (!$name) {
            return $this->handleView($this->view(null, Response::HTTP_NOT_FOUND));
        }

        /** @var $item AddressType */
        $item = $this->getDoctrine()->getRepository('OroAddressBundle:AddressType')->find($name);

        return $this->handleView(
            $this->view($item, is_object($item) ? Response::HTTP_OK : Response::HTTP_NOT_FOUND)
        );
    }
}
