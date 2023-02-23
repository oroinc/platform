<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * The registry returns the documentation parser for a specific request type.
 */
class ResourceDocParserRegistry
{
    /** @var array [[parser service id, request type expression], ...] */
    private array $parsers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    /**
     * @param array                    $parsers [[parser service id, request type expression], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(array $parsers, ContainerInterface $container, RequestExpressionMatcher $matcher)
    {
        $this->parsers = $parsers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Gets an instance of ResourceDocParserInterface
     * responsible to parse documentation for API resources for the specific request type.
     *
     * @throws \LogicException if a parser cannot be created for the given request type
     */
    public function getParser(RequestType $requestType): ResourceDocParserInterface
    {
        $parserServiceId = null;
        foreach ($this->parsers as [$serviceId, $expression]) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $parserServiceId = $serviceId;
                break;
            }
        }
        if (null === $parserServiceId) {
            throw new \LogicException(sprintf(
                'Cannot find a resource documentation parser for the request "%s".',
                (string)$requestType
            ));
        }

        return $this->container->get($parserServiceId);
    }
}
