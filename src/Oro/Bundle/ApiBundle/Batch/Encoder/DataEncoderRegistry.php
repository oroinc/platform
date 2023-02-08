<?php

namespace Oro\Bundle\ApiBundle\Batch\Encoder;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get the data encoder for a specific request type.
 */
class DataEncoderRegistry
{
    /** @var array [[encoder service id, request type expression], ...] */
    private array $encoders;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    /**
     * @param array                    $encoders [[encoder service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(array $encoders, ContainerInterface $container, RequestExpressionMatcher $matcher)
    {
        $this->encoders = $encoders;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Returns the data encoder for the given request type.
     */
    public function getEncoder(RequestType $requestType): ?DataEncoderInterface
    {
        foreach ($this->encoders as [$serviceId, $expression]) {
            if ($this->matcher->matchValue($expression, $requestType)) {
                return $this->container->get($serviceId);
            }
        }

        return null;
    }
}
