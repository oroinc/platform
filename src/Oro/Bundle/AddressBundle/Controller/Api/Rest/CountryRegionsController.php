<?php

namespace Oro\Bundle\AddressBundle\Controller\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to get regions by country.
 */
class CountryRegionsController extends RestGetController
{
    /**
     * REST GET regions by country
     *
     * @param Country|null $country
     *
     * @ApiDoc(
     *      description="Get regions by country id",
     *      resource=true
     * )
     * @return Response
     */
    public function getAction(Country $country = null)
    {
        if (null === $country) {
            return $this->handleView($this->view(null, Response::HTTP_NOT_FOUND));
        }

        /** @var RegionRepository $regionRepository */
        $regionRepository = $this->container->get('doctrine')->getRepository(Region::class);
        $regions = $regionRepository->getCountryRegions($country);
        $manager = $this->getManager();
        $serializedRegions = $manager->serializeEntities($regions);

        return new JsonResponse($serializedRegions, Response::HTTP_OK);
    }

    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_address.api.manager.region');
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            ['doctrine' => ManagerRegistry::class]
        );
    }
}
