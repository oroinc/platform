<?php

namespace Oro\Bundle\EntityBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Entities controller.
 * @Route("/dictionary")
 */
class DictionaryController extends Controller
{
    /**
     * Get dictionary values by search query
     *
     * @param string $dictionary - Class Name Entity that was configured as Dictionary
     *
     * @Route(
     *      "/{dictionary}/search",
     *      name="oro_dictionary_search"
     * )
     *
     * @return JsonResponse
     */
    public function searchAction($dictionary)
    {
        $searchQuery = $this->get('request_stack')->getCurrentRequest()->get('q');
        $manager = $this->container->get('oro_entity.manager.dictionary.api');
        $manager->setClass($manager->resolveEntityClass($dictionary, true));
        $results = $manager->findValueBySearchQuery($searchQuery);
        $responseContext = ['results' => $results];

        return new JsonResponse($responseContext);
    }

    /**
     * Get dictionary values by keys
     *
     * @param string $dictionary - Class Name Entity that was configured as Dictionary
     *
     * @Route(
     *      "/{dictionary}/values",
     *      name="oro_dictionary_value"
     * )
     *
     * @return JsonResponse
     */
    public function valuesAction($dictionary)
    {
        $keys = $this->get('request_stack')->getCurrentRequest()->get('keys');
        $manager = $this->container->get('oro_entity.manager.dictionary.api');
        $manager->setClass($manager->resolveEntityClass($dictionary, true));
        $result = $manager->findValueByPrimaryKey($keys);
        $responseContext = ['results' => $result];

        return new JsonResponse($responseContext);
    }
}
