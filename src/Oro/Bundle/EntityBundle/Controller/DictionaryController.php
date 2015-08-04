<?php

namespace Oro\Bundle\EntityBundle\Controller;

use Rhumsaa\Uuid\Console\Exception;

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
     * Get count values of a dictionary.
     *
     * @param string $dictionary - Class Name Entity that was configured as Dictionary
     *
     * @Route(
     *      "{dictionary}/count",
     *      name="oro_dictionary_count"
     * )
     *
     * @return JsonResponse
     */
    public function countAction($dictionary)
    {
        $manager = $this->container->get('oro_entity.manager.dictionary.api');
        $manager->setClass($manager->resolveEntityClass($dictionary, true));

        $resalt = $manager->count();

        $responseContext = ['result' => $resalt];

        return new JsonResponse($responseContext);
    }

    /**
     * Get dictionary values by search query
     *
     * @param string $dictionary - Class Name Entity that was configured as Dictionary
     *
     * @Route(
     *      "{dictionary}/search",
     *      name="oro_dictionary_search"
     * )
     *
     * @return JsonResponse
     */
    public function searchAction($dictionary)
    {
        try {
            $searchQuery = $this->get('request_stack')->getCurrentRequest()->get('q');
            $manager = $this->container->get('oro_entity.manager.dictionary.api');
            $manager->setClass($manager->resolveEntityClass($dictionary, true));
            $results = $manager->findValueBySearchQuery($searchQuery);
            $responseContext = ['results' => $results];
        } catch (Exception $e) {
            $responseContext = ['error' => $e->getMessage()];
        }

        return new JsonResponse($responseContext);
    }

    /**
     * Get dictionary values by keys
     *
     * @param string $dictionary - Class Name Entity that was configured as Dictionary
     *
     * @Route(
     *      "{dictionary}/values",
     *      name="oro_dictionary_value"
     * )
     *
     * @return JsonResponse
     */
    public function valuesAction($dictionary)
    {
        try {
            $keys = $this->get('request_stack')->getCurrentRequest()->get('keys');
            $manager = $this->container->get('oro_entity.manager.dictionary.api');
            $manager->setClass($manager->resolveEntityClass($dictionary, true));
            $result = $manager->findValueByPrimaryKey($keys);
            $responseContext = ['results' => $result];
        } catch (Exception $e) {
            $responseContext = ['error' => $e->getMessage()];
        }

        return new JsonResponse($responseContext);
    }
}
