<?php

namespace Oro\Bundle\ConfigBundle\Provider;

class ChainSearchProvider implements SearchProviderInterface
{
    /** @var SearchProviderInterface[] */
    private $providers = [];

    /**
     * @param SearchProviderInterface $provider
     */
    public function addProvider(SearchProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return 0 !== count($this->providers);
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
