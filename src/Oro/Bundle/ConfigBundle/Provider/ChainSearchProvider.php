<?php

namespace Oro\Bundle\ConfigBundle\Provider;

/**
 * Collects configuration search data from all applicable child providers.
 */
class ChainSearchProvider implements SearchProviderInterface
{
    /** @var iterable|SearchProviderInterface[] */
    private $providers;

    /**
     * @param iterable|SearchProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($name)
    {
        $data = [];
        foreach ($this->providers as $provider) {
            if ($provider->supports($name)) {
                $data[] = $provider->getData($name);
            }
        }
        if ($data) {
            $data = array_merge(...$data);
        }

        return $data;
    }
}
