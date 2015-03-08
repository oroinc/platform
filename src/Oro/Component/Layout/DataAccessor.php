<?php

namespace Oro\Component\Layout;

/**
 * The data accessor that falls back to the layout context if a data provider
 * is not registered in the layout registry.
 * This means that at first the data provider is searched in the layout registry and
 * if it is not registered there, data are searched in the context.
 */
class DataAccessor implements DataAccessorInterface
{
    /** @var LayoutRegistryInterface */
    private $registry;

    /** @var ContextInterface */
    private $context;

    /** @var array */
    private $dataProviders = [];

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
    public function getIdentifier($name)
    {
        $dataProvider = $this->getDataProvider($name);
        if ($dataProvider === false) {
            throw new Exception\InvalidArgumentException(
                sprintf('Could not load the data provider "%s".', $name)
            );
        } elseif ($dataProvider instanceof DataProviderInterface) {
            return $dataProvider->getIdentifier();
        } else {
            return $dataProvider;
        }
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
        $dataProvider = $this->getDataProvider($name);
        if ($dataProvider === false) {
            throw new Exception\InvalidArgumentException(
                sprintf('Could not load the data provider "%s".', $name)
            );
        } elseif ($dataProvider instanceof DataProviderInterface) {
            return $dataProvider->getData();
        } else {
            return $this->context->data()->get($name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($name)
    {
        return $this->getDataProvider($name) !== false;
    }

    /**
     * Implements \ArrayAccess
     *
     * @throws \BadMethodCallException always as changing data providers is not allowed
     */
    public function offsetSet($name, $value)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * Implements \ArrayAccess
     *
     * @throws \BadMethodCallException always as removing data providers is not allowed
     */
    public function offsetUnset($name)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * @param string $name The name of the data provider
     *
     * @return mixed The returned values:
     *               DataProviderInterface if the data provider is loaded
     *               string if data should be loaded from the layout context
     *               bool if the requested data cannot be loaded
     */
    protected function getDataProvider($name)
    {
        if (isset($this->dataProviders[$name])) {
            return $this->dataProviders[$name];
        }

        $dataProvider = $this->registry->findDataProvider($name);
        if ($dataProvider !== null) {
            if ($dataProvider instanceof ContextAwareInterface) {
                $dataProvider->setContext($this->context);
            }
        } elseif ($this->context->data()->has($name)) {
            $dataProvider = $this->context->data()->getIdentifier($name);
        } else {
            $dataProvider = false;
        }
        $this->dataProviders[$name] = $dataProvider;

        return $dataProvider;
    }
}
