<?php

namespace Oro\Bundle\EntityBundle\Controller;

use Oro\Bundle\EntityBundle\Provider\DictionaryEntityDataProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller to get dictionary values.
 */
#[Route(path: '/dictionary')]
class DictionaryController
{
    private DictionaryEntityDataProvider $dictionaryEntityDataProvider;

    public function __construct(DictionaryEntityDataProvider $dictionaryEntityDataProvider)
    {
        $this->dictionaryEntityDataProvider = $dictionaryEntityDataProvider;
    }

    /**
     * Gets dictionary values by search query.
     */
    #[Route(path: '/{dictionary}/search', name: 'oro_dictionary_search')]
    public function searchAction(string $dictionary, Request $request): JsonResponse
    {
        return $this->getJsonResponse(
            $this->dictionaryEntityDataProvider->getValuesBySearchQuery($dictionary, $request->get('q'))
        );
    }

    /**
     * Gets dictionary values by keys.
     */
    #[Route(path: '/{dictionary}/values', name: 'oro_dictionary_value')]
    public function valuesAction(string $dictionary, Request $request): JsonResponse
    {
        $keys = $request->get('keys');

        return $this->getJsonResponse(
            $keys ? $this->dictionaryEntityDataProvider->getValuesByIds($dictionary, $keys) : []
        );
    }

    private function getJsonResponse(array $data): JsonResponse
    {
        return new JsonResponse(['results' => $data]);
    }
}
