<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The registry returns the documentation parser for a specific request type.
 * The implementation of this registry assumes that all parsers
 * are declared in DIC as public services.
 * @see \Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ResourceDocParserCompilerPass
 */
class ResourceDocParserRegistry
{
    /** @var array [[parser service id, request type expression], ...] */
    private $parsers;

    /** @var ContainerInterface */
    private $container;

    /** @var RequestExpressionMatcher */
    private $matcher;

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
     * responsible to parse documentation for Data API resources for the specific request type.
     *
     * @param RequestType $requestType
     *
     * @return ResourceDocParserInterface
     *
     * @throws \LogicException if a parser cannot be created for the given request type
     */
    public function getParser(RequestType $requestType): ResourceDocParserInterface
    {
        $parserServiceId = null;
        foreach ($this->parsers as list($serviceId, $expression)) {
            if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                $parserServiceId = $serviceId;
                break;
            }
        }
        if (null === $parserServiceId) {
            throw new \LogicException(
                sprintf('Cannot find a resource documentation parser for the request "%s".', (string)$requestType)
            );
        }

        return $this->container->get($parserServiceId);
    }
}
