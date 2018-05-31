<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

/**
 * Contains all entity identifier value transformers
 * and allows to get a transformer suitable for a specific request type.
 */
class EntityIdTransformerRegistry
{
    /** @var array [[EntityIdTransformerInterface, request type expression], ...] */
    private $transformers;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /** @var array [request type => EntityIdTransformerInterface, ...] */
    private $cache = [];

    /**
     * @param array                    $transformers [[EntityIdTransformerInterface, request type expression], ...]
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(array $transformers, RequestExpressionMatcher $matcher)
    {
        $this->transformers = $transformers;
        $this->matcher = $matcher;
    }

    /**
     * Gets entity identifier value transformer for the given request type.
     *
     * @param RequestType $requestType
     *
     * @return EntityIdTransformerInterface|null
     */
    public function getEntityIdTransformer(RequestType $requestType)
    {
        $cacheKey = (string)$requestType;
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $entityIdTransformer = null;
        foreach ($this->transformers as list($transformer, $expression)) {
            if ($this->matcher->matchValue($expression, $requestType)) {
                $entityIdTransformer = $transformer;
                break;
            }
        }

        $this->cache[$cacheKey] = $entityIdTransformer;

        return $entityIdTransformer;
    }
}
