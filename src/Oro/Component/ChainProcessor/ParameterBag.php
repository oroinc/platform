<?php

namespace Oro\Component\ChainProcessor;

/**
 * The container for key/value pairs.
 */
class ParameterBag extends AbstractParameterBag
{
    /** @var array [key => value, ...] */
    private $items = [];

    /** @var ParameterValueResolverInterface[] [key => ParameterValueResolverInterface, ...] */
    private $resolvers = [];

    /** @var array [key => value, ...] */
    private $resolvedItems = [];

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (!\array_key_exists($key, $this->items)) {
            return null;
        }

        if (\array_key_exists($key, $this->resolvedItems)) {
            return $this->resolvedItems[$key];
        }

        $value = $this->items[$key];
        if (isset($this->resolvers[$key])) {
            $resolver = $this->resolvers[$key];
            if ($resolver->supports($value)) {
                $value = $resolver->resolve($value);
            }
            $this->resolvedItems[$key] = $value;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->items[$key] = $value;
        unset($this->resolvedItems[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function setResolver($key, ?ParameterValueResolverInterface $resolver)
    {
        if (null === $resolver) {
            unset($this->resolvers[$key]);
        } else {
            $this->resolvers[$key] = $resolver;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->items[$key], $this->resolvedItems[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            if (\array_key_exists($key, $this->resolvedItems)) {
                $value = $this->resolvedItems[$key];
            } elseif (isset($this->resolvers[$key])) {
                $resolver = $this->resolvers[$key];
                if ($resolver->supports($value)) {
                    $value = $resolver->resolve($value);
                }
                $this->resolvedItems[$key] = $value;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->items = [];
        $this->resolvedItems = [];
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return \count($this->items);
    }
}
