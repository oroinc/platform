<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

/**
 * The interface for registry of autocomplete search handlers.
 */
interface SearchRegistryInterface
{
    public function getSearchHandler(string $name): SearchHandlerInterface;
    public function hasSearchHandler(string $name): bool;
}
