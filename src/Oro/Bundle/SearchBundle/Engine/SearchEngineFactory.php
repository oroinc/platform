<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Factory to create targeted search engine instance(orm, elastic_search).
 */
class SearchEngineFactory
{
    /**
     * @param ServiceLocator $locator
     * @param EngineParameters $engineParameters
     * @return EngineInterface
     * @throws UnexpectedTypeException
     */
    public static function create(
        ServiceLocator $locator,
        EngineParameters $engineParameters
    ): EngineInterface {
        $engine = $locator->get($engineParameters->getEngineName());
        if (!$engine instanceof EngineInterface) {
            throw new UnexpectedTypeException($engine, EngineInterface::class);
        }

        return $engine;
    }
}
