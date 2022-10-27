<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Psr\Container\ContainerInterface;

/**
 * The container for variable processors.
 */
class VariableProcessorRegistry
{
    /** @var ContainerInterface */
    private $processors;

    public function __construct(ContainerInterface $processors)
    {
        $this->processors = $processors;
    }

    public function has(string $alias): bool
    {
        return $this->processors->has($alias);
    }

    public function get(string $alias): VariableProcessorInterface
    {
        if (!$this->processors->has($alias)) {
            throw new \LogicException(sprintf('Unknown variable processor with alias "%s".', $alias));
        }

        return $this->processors->get($alias);
    }
}
