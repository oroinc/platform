<?php

namespace Oro\Bundle\ApiBundle\PostProcessor;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * Contains all post processors and allows to get a post processor by its name
 * and suitable for a specific request type.
 */
class PostProcessorRegistry
{
    /** @var array [name => [[post processor service id, request type expression], ...], ...] */
    private array $postProcessors;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    /**
     * @param array                    $postProcessors [name => [[service id, request type expression], ...], ...]
     * @param ContainerInterface       $container
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(
        array $postProcessors,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->postProcessors = $postProcessors;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * Gets names of all registered post processors.
     *
     * @return string[]
     */
    public function getPostProcessorNames(): array
    {
        return array_keys($this->postProcessors);
    }

    /**
     * Gets a post processor by its name and suitable for the given request type.
     */
    public function getPostProcessor(string $name, RequestType $requestType): ?PostProcessorInterface
    {
        $result = null;
        if (isset($this->postProcessors[$name])) {
            foreach ($this->postProcessors[$name] as [$serviceId, $expression]) {
                if (!$expression || $this->matcher->matchValue($expression, $requestType)) {
                    $result = $this->container->get($serviceId);
                    break;
                }
            }
        }

        return $result;
    }
}
