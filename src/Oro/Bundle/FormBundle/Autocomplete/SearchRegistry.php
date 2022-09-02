<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

use Psr\Container\ContainerInterface;

/**
 * The registry of autocomplete search handlers.
 */
class SearchRegistry
{
    private ContainerInterface $searchHandlers;

    public function __construct(ContainerInterface $searchHandlers)
    {
        $this->searchHandlers = $searchHandlers;
    }

    /**
     * @throws \RuntimeException if a handler with the given name does not exist
     */
    public function getSearchHandler(string $name): SearchHandlerInterface
    {
        if (!$this->searchHandlers->has($name)) {
            throw new \RuntimeException(sprintf('Search handler "%s" is not registered.', $name));
        }

        return $this->searchHandlers->get($name);
    }

    public function hasSearchHandler(string $name): bool
    {
        return $this->searchHandlers->has($name);
    }
}
