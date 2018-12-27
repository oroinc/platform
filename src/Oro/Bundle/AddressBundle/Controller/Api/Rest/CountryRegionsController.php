<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * CountryRegions controller
 * @RouteResource("country/regions")
 * @NamePrefix("oro_api_country_")
 */
class CountryRegionsController extends FOSRestController
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
                $this->view(null, Codes::HTTP_NOT_FOUND)
            );
        }

        /** @var $regionRepository RegionRepository */
        $regionRepository = $this->getDoctrine()->getRepository('OroAddressBundle:Region');
        $regions = $regionRepository->getCountryRegions($country);

        return $this->handleView(
            $this->view($regions, Codes::HTTP_OK)
        );
    }
}
