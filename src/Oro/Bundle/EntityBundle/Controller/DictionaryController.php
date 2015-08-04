<?php

namespace Oro\Bundle\EntityBundle\Controller;

use FOS\RestBundle\Util\Codes;

use Rhumsaa\Uuid\Console\Exception;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Entities controller.
 * @Route("/dictionary")
 */
class DictionaryController extends Controller
{

    /**
     * Get count values of a dictionary entity.
     *
     * @param string $dictionary The URL safe name or plural alias of a dictionary entity.
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
     * Grid of Custom/Extend entity.
     *
     * @param string $dictionary
     *
     * @return array
     *
     * @Route(
     *      "filter/{dictionary}",
     *      name="oro_dictionary_filter"
     * )
     * @Template()
     */
    public function filterAction($dictionary)
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
     * Grid of Custom/Extend entity.
     *
     * @param string $dictionary
     *
     * @return array
     *
     * @Route(
     *      "values/{dictionary}",
     *      name="oro_dictionary_value"
     * )
     * @Template()
     */
    public function loadValuesAction($dictionary)
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
