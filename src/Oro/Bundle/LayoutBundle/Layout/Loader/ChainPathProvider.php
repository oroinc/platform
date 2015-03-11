<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;

class ChainPathProvider implements ContextAwareInterface, PathProviderInterface
{
    /** @var array */
    protected $providers = [];

    /** @var array */
    protected $sorted;

    /**
     * For automatically injecting provider should be registered as DI service
     * with tag layout.resource.path_provider
     *
     * @param PathProviderInterface $provider
     * @param int                   $priority
     */
    public function addProvider(PathProviderInterface $provider, $priority = 0)
    {
        $this->providers[$priority][] = $provider;
        $this->sorted                 = null;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(ContextInterface $context)
    {
        foreach ($this->getProviders() as $provider) {
            if ($provider instanceof ContextAwareInterface) {
                $provider->setContext($context);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPaths()
    {
        $result = [];

        foreach ($this->getProviders() as $provider) {
            $result = array_merge($result, $provider->getPaths());
        }

        return array_unique($result, SORT_STRING);
    }

    /**
     * @return PathProviderInterface[]
     */
    protected function getProviders()
    {
        if (!$this->sorted) {
            krsort($this->providers);
            $this->sorted = !empty($this->providers) ? call_user_func_array('array_merge', $this->providers) : [];
        }

        return $this->sorted;
    }
}
