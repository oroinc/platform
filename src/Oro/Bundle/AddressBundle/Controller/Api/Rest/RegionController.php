<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for regions.
 */
class RegionController extends AbstractFOSRestController
{
    /**
     * REST GET region by id
     *
     * @QueryParam(name="id", nullable=false)
     *
     * @ApiDoc(
     *     description="Get region by id",
     *     resource=true,
     *     requirements={
     *         {"name"="id", "dataType"="string", "requirement"="\d+", "description"="region combined code"}
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function getAction(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return $this->handleView($this->view(null, Response::HTTP_NOT_FOUND));
        }

        /** @var  $item \Oro\Bundle\AddressBundle\Entity\Region */
        $item = $this->getDoctrine()->getRepository('OroAddressBundle:Region')->find($id);

        return $this->handleView(
            $this->view($item, is_object($item) ? Response::HTTP_OK : Response::HTTP_NOT_FOUND)
        );
    }
}
