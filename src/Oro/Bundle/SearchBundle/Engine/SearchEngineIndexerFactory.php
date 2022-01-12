<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Factory to create targeted search engine indexer instance(orm, elastic_search).
 */
class SearchEngineIndexerFactory
{
    /**
     * @param ServiceLocator $locator
     * @param EngineParameters $engineParameters
     * @return IndexerInterface
     * @throws UnexpectedTypeException
     */
    public static function create(
        ServiceLocator $locator,
        EngineParameters $engineParameters
    ): IndexerInterface {
        $engineIndexer = $locator->get($engineParameters->getEngineName());
        if (!$engineIndexer instanceof IndexerInterface) {
            throw new UnexpectedTypeException($engineIndexer, IndexerInterface::class);
        }

        return $engineIndexer;
    }
}
