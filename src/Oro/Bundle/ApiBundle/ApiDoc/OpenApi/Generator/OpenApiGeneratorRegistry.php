<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Generator;

use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Exception\RenderInvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get the OpenAPI specification generator for a specific view.
 */
class OpenApiGeneratorRegistry
{
    /** @var string[] */
    private array $views;
    private ContainerInterface $generators;

    public function __construct(array $views, ContainerInterface $generators)
    {
        $this->views = $views;
        $this->generators = $generators;
    }

    /**
     * @return string[]
     */
    public function getViews(): array
    {
        return $this->views;
    }

    /**
     * @throws RenderInvalidArgumentException If a generator for the given view does not exist
     */
    public function getGenerator(string $view): OpenApiGeneratorInterface
    {
        if (!$this->generators->has($view)) {
            throw new RenderInvalidArgumentException(sprintf('The view "%s" is not supported.', $view));
        }

        return $this->generators->get($view);
    }
}
