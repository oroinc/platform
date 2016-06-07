<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LayoutContext implements ContextInterface
{
    /** @var array */
    protected $items = [];

    /** @var ContextDataCollection */
    protected $dataCollection;

    /** @var OptionsResolver */
    protected $resolver;

    /** @var boolean */
    protected $resolved = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dataCollection = new ContextDataCollection($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getResolver()
    {
        if ($this->resolver === null) {
            $this->resolver = $this->createResolver();
        }

        return $this->resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve()
    {
        if ($this->resolved) {
            throw new Exception\LogicException('The context variables are already resolved.');
        }

        try {
            $this->items = $this->getResolver()->resolve($this->items);

            // validate that all added objects implement ContextItemInterface
            foreach ($this->items as $name => $value) {
                if (is_object($value) && !$value instanceof ContextItemInterface) {
                    throw new InvalidOptionsException(
                        sprintf(
                            'The option "%s" has invalid type. Expected "%s", but "%s" given.',
                            $name,
                            'Oro\Component\Layout\ContextItemInterface',
                            get_class($value)
                        )
                    );
                }
            }

            $this->resolved = true;
        } catch (OptionsResolverException $e) {
            throw new Exception\LogicException(
                sprintf('Failed to resolve the context variables. Reason: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isResolved()
    {
        return $this->resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return isset($this->items[$name]) || array_key_exists($name, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!isset($this->items[$name]) && !array_key_exists($name, $this->items)) {
            throw new \OutOfBoundsException(sprintf('Undefined index: %s.', $name));
        };

        return $this->items[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getOr($name, $default = null)
    {
        return isset($this->items[$name]) || array_key_exists($name, $this->items)
            ? $this->items[$name]
            : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        if ($this->resolved && !$this->has($name)) {
            throw new Exception\LogicException(
                sprintf('The item "%s" cannot be added because the context variables are already resolved.', $name)
            );
        }

        $this->items[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        if ($this->resolved && $this->has($name)) {
            throw new Exception\LogicException(
                sprintf('The item "%s" cannot be removed because the context variables are already resolved.', $name)
            );
        }

        unset($this->items[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function data()
    {
        return $this->dataCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($name)
    {
        return isset($this->items[$name]) || array_key_exists($name, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($name)
    {
        if (!isset($this->items[$name]) && !array_key_exists($name, $this->items)) {
            throw new \OutOfBoundsException(sprintf('Undefined index: %s.', $name));
        };

        return $this->items[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }

    /**
     * @return OptionsResolver
     */
    protected function createResolver()
    {
        return new OptionsResolver();
    }
}
