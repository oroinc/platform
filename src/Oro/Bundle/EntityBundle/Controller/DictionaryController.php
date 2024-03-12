<?php

namespace Oro\Bundle\EntityBundle\Controller;

use Oro\Bundle\EntityBundle\Entity\Manager\DictionaryApiEntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Entities controller.
 */
#[Route(path: '/dictionary')]
class DictionaryController extends AbstractController
{
    /**
     * Get dictionary values by search query
     *
     * @param string $dictionary - Class Name Entity that was configured as Dictionary
     *
     *
     * @return JsonResponse
     */
    #[Route(path: '/{dictionary}/search', name: 'oro_dictionary_search')]
    public function searchAction($dictionary)
    {
        $searchQuery = $this->container->get('request_stack')->getCurrentRequest()->get('q');
        $manager = $this->container->get(DictionaryApiEntityManager::class);
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
     *
     * @return JsonResponse
     */
    #[Route(path: '/{dictionary}/values', name: 'oro_dictionary_value')]
    public function valuesAction($dictionary)
    {
        $keys = $this->container->get('request_stack')->getCurrentRequest()->get('keys');
        $manager = $this->container->get(DictionaryApiEntityManager::class);
        $manager->setClass($manager->resolveEntityClass($dictionary, true));
        $result = $manager->findValueByPrimaryKey($keys);
        $responseContext = ['results' => $result];

        return new JsonResponse($responseContext);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                DictionaryApiEntityManager::class,
            ]
        );
    }
}
