<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * Contains all data transformers and allows to get a data transformer by a data type
 * and suitable for a specific request type.
 */
class DataTransformerRegistry
{
    /** @var array [data type => [[transformer service id, request type expression], ...], ...] */
    private array $transformers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    /**
     * @param array                    $transformers [data type => [[service id, request type expression], ...], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(array $transformers, ContainerInterface $container, RequestExpressionMatcher $matcher)
    {
        $this->transformers = $transformers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Gets a data transformer for a given data type and suitable for the given request type.
     *
     * @param string      $dataType
     * @param RequestType $requestType
     *
     * @return mixed Can be NULL,
     *               an instance of {@see \Oro\Component\EntitySerializer\DataTransformerInterface}
     *               or {@see \Symfony\Component\Form\DataTransformerInterface}.
     */
    public function getDataTransformer(string $dataType, RequestType $requestType): mixed
    {
        $result = null;
        if (isset($this->transformers[$dataType])) {
            foreach ($this->transformers[$dataType] as [$serviceId, $expression]) {
                if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                    $result = $this->container->get($serviceId);
                    break;
                }
            }
        }

        return $result;
    }
}
