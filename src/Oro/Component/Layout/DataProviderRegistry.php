<?php

namespace Oro\Component\Layout;

/**
 * The data provider registry that fallback to the layout context if a data provider
 * is not registered in the layout registry.
 * This means that at first a data provider is searching in the layout registry and
 * if it does not registered there a context variable with appropriate name is used
 * as data.
 */
class DataProviderRegistry implements DataProviderRegistryInterface
{
    /** @var LayoutRegistryInterface */
    private $registry;

    /** @var ContextInterface */
    private $context;

    /** @var ContextAwareDataProvider[] */
    private $contextAwareDataProviders = [];

    /**
     * @param LayoutRegistryInterface $registry
     * @param ContextInterface        $context
     */
    public function __construct(LayoutRegistryInterface $registry, ContextInterface $context)
    {
        $this->registry = $registry;
        $this->context  = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($name)
    {
        if (isset($this->contextAwareDataProviders[$name])) {
            return $this->contextAwareDataProviders[$name];
        }

        $dataProvider = $this->registry->findDataProvider($name);
        if ($dataProvider === null) {
            // try to use the layout context as a data provider
            if (!isset($this->context[$name])) {
                throw new Exception\InvalidArgumentException(sprintf('Could not load a data provider "%s".', $name));
            }

            $dataProvider = new ContextAwareDataProvider($this->context, $name);

            $this->contextAwareDataProviders[$name] = $dataProvider;
        }

        return $dataProvider;
    }

    /**
     * Implements \ArrayAccess
     *
     * @throws \BadMethodCallException always as checking existence of a data providers is not supported yet
     */
    public function offsetExists($name)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * Implements \ArrayAccess
     *
     * @throws \BadMethodCallException always as changing a data providers is not allowed
     */
    public function offsetSet($name, $value)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * Implements \ArrayAccess
     *
     * @throws \BadMethodCallException always as removing a data providers is not allowed
     */
    public function offsetUnset($name)
    {
        throw new \BadMethodCallException('Not supported');
    }
}
