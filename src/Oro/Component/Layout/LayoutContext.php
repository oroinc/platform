<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;

class LayoutContext implements ContextInterface
{
    /** @var array */
    protected $data = [];

    /** @var OptionsResolverInterface */
    protected $resolver;

    /** @var boolean */
    protected $resolved = false;

    /**
     * {@inheritdoc}
     */
    public function getDataResolver()
    {
        if ($this->resolver === null) {
            $this->resolver = new OptionsResolver();
        }

        return $this->resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve()
    {
        if ($this->resolved) {
            throw new Exception\LogicException('The context data are already resolved.');
        }

        try {
            $this->data     = $this->getDataResolver()->resolve($this->data);
            $this->resolved = true;
        } catch (OptionsResolverException $e) {
            throw new Exception\LogicException(
                sprintf('Failed to resolve the context data. Reason: %s', $e->getMessage()),
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
        return isset($this->data[$name]) || array_key_exists($name, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!isset($this->data[$name]) && !array_key_exists($name, $this->data)) {
            throw new \OutOfBoundsException(sprintf('Undefined index: %s.', $name));
        };

        return $this->data[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getOr($name, $default = null)
    {
        return isset($this->data[$name]) || array_key_exists($name, $this->data)
            ? $this->data[$name]
            : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        if ($this->resolved && !$this->has($name)) {
            throw new Exception\LogicException(
                sprintf('The item "%s" cannot be added because the context data are already resolved.', $name)
            );
        }

        $this->data[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        if ($this->resolved && $this->has($name)) {
            throw new Exception\LogicException(
                sprintf('The item "%s" cannot be removed because the context data are already resolved.', $name)
            );
        }

        unset($this->data[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}
