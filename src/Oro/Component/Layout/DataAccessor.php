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

        if (is_object($dataProvider)) {
            return new DataProviderDecorator($dataProvider, ['get', 'has', 'is']);
        } elseif ($dataProvider !== false) {
            return $this->context->data()->get($name);
        }

        throw new Exception\InvalidArgumentException(
            sprintf('Could not load the data provider "%s".', $name)
        );
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
     * @param mixed $name
     * @param mixed $value
     */
    public function offsetSet($name, $value)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * Implements \ArrayAccess
     *
     * @param mixed $name
     */
    public function offsetUnset($name)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * @param string $name The name of the data provider
     *
     * @return mixed The returned values:
     *               data provider object if the data provider is loaded
     *               mixed if data should be loaded from the layout context
     *               false if the requested data cannot be loaded
     */
    protected function getDataProvider($name)
    {
        if (!isset($this->dataProviders[$name])) {
            $dataProvider = $this->registry->findDataProvider($name);

            if ($dataProvider === null) {
                $dataProvider = $this->context->data()->has($name) ? true : false;
            }

            $this->dataProviders[$name] = $dataProvider;
        }

        return $this->dataProviders[$name];
    }
}
