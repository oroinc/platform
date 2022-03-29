<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

/**
 * Returns the form options collected from the underlying providers.
 */
class ExtendFieldFormOptionsProvider implements ExtendFieldFormOptionsProviderInterface
{
    /** @var iterable<ExtendFieldFormOptionsProviderInterface> */
    private iterable $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function getOptions(string $className, string $fieldName): array
    {
        $options = [];
        foreach ($this->providers as $provider) {
            $options[] = $provider->getOptions($className, $fieldName);
        }

        return array_replace_recursive([], ...$options);
    }
}
