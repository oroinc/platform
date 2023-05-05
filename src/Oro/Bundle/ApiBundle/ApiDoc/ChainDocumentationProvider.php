<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Psr\Container\ContainerInterface;

/**
 * Joins documentation from all child documentation providers.
 */
class ChainDocumentationProvider implements DocumentationProviderInterface
{
    /** @var array [[provider service id, request type expression], ...] */
    private array $providers;
    private ContainerInterface $container;
    private RequestExpressionMatcher $matcher;

    public function __construct(
        array $providers,
        ContainerInterface $container,
        RequestExpressionMatcher $matcher
    ) {
        $this->providers = $providers;
        $this->container = $container;
        $this->matcher = $matcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentation(RequestType $requestType): ?string
    {
        $paragraphs = [];
        foreach ($this->providers as [$serviceId, $expression]) {
            if ($this->isMatched($expression, $requestType)) {
                $provider = $this->instantiateProvider($serviceId);
                $documentation = $provider->getDocumentation($requestType);
                if ($documentation) {
                    $paragraphs[] = $documentation;
                }
            }
        }

        if (empty($paragraphs)) {
            return null;
        }

        return implode("\n\n", $paragraphs);
    }

    private function isMatched(mixed $expression, RequestType $requestType): bool
    {
        return !$expression || $this->matcher->matchValue($expression, $requestType);
    }

    private function instantiateProvider(string $serviceId): DocumentationProviderInterface
    {
        return $this->container->get($serviceId);
    }
}
