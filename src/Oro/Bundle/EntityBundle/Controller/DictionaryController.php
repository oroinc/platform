<?php

namespace Oro\Bundle\EntityBundle\Controller;

use Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Entities controller.
 * @Route("/dictionary")
 */
class DictionaryController extends AbstractController
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
        $manager = $this->get(DictionaryApiEntityManager::class);
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
        $manager = $this->get(DictionaryApiEntityManager::class);
        $manager->setClass($manager->resolveEntityClass($dictionary, true));
        $result = $manager->findValueByPrimaryKey($keys);
        $responseContext = ['results' => $result];

        return new JsonResponse($responseContext);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                DictionaryApiEntityManager::class,
            ]
        );
    }
}
