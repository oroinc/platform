<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * Contains all data transformers
 * and allows to get a transformer suitable for a specific request type.
 * @see \Oro\Component\EntitySerializer\DataTransformerInterface
 */
class DataTransformerRegistry
{
    /** @var array [data type => [[transformer service id, request type expression], ...], ...] */
    private $transformers;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

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
     * Returns a data transformer for a given data type.
     *
     * @param string      $dataType
     * @param RequestType $requestType
     *
     * @return mixed|null Can be NULL,
     *                    an instance of "Oro\Component\EntitySerializer\DataTransformerInterface"
     *                    or "Symfony\Component\Form\DataTransformerInterface".
     */
    public function getDataTransformer($dataType, RequestType $requestType)
    {
        $result = null;
        if (isset($this->transformers[$dataType])) {
            foreach ($this->transformers[$dataType] as list($serviceId, $expression)) {
                if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                    $result = $this->container->get($serviceId);
                    break;
                }
            }
        }

        return $result;
    }
}
