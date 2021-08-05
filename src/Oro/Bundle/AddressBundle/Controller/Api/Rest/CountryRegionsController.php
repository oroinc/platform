<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to get regions by country.
 */
class CountryRegionsController extends AbstractFOSRestController
{
    /**
     * REST GET regions by country
     *
     * @param Country $country
     *
     * @ApiDoc(
     *      description="Get regions by country id",
     *      resource=true
     * )
     * @return Response
     */
    public function getAction(Country $country = null)
    {
        if (!$country) {
            return $this->handleView(
                $this->view(null, Response::HTTP_NOT_FOUND)
            );
        }

        /** @var $regionRepository RegionRepository */
        $regionRepository = $this->getDoctrine()->getRepository('OroAddressBundle:Region');
        $regions = $regionRepository->getCountryRegions($country);

        return $this->handleView(
            $this->view($regions, Response::HTTP_OK)
        );
    }
}
