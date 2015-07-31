<?php

namespace Oro\Bundle\EntityBundle\Controller;

use Rhumsaa\Uuid\Console\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Util\Codes;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Entities controller.
 * @Route("/dictionary")
 */
class DictionaryController extends Controller
{
    /**
     * Grid of Custom/Extend entity.
     *
     * @param string $entityName
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
     * @param string $entityName
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
