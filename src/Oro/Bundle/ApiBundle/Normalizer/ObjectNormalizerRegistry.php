<?php

namespace Oro\Bundle\ApiBundle\Normalizer;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * Contains all object normalisers
 * and allows to get a normaliser suitable for a specific object type and a request type.
 */
class ObjectNormalizerRegistry
{
    /** @var array [[normalizer service id, object class, request type expression], ...] */
    private array $normalizers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    /**
     * @param array                    $normalizers [[service id, object class, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(array $normalizers, ContainerInterface $container, RequestExpressionMatcher $matcher)
    {
        $this->normalizers = $normalizers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Gets a normalizer for a given object.
     */
    public function getObjectNormalizer(object $object, RequestType $requestType): ?ObjectNormalizerInterface
    {
        $result = null;
        foreach ($this->normalizers as [$serviceId, $objectClass, $expression]) {
            if (is_a($object, $objectClass)
                && (!$expression || $this->matcher->matchValue($expression, $requestType))
            ) {
                $result = $this->container->get($serviceId);
                break;
            }
        }

        return $result;
    }
}
