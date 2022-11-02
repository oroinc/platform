<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Context for rendering layout blocks
 */
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

    /** @var string */
    protected $hash;

    /**
     * @param array          $parameters Context items
     * @param array|string[] $vars       Array of allowed layout context variables
     */
    public function __construct(array $parameters = [], array $vars = [])
    {
        $this->dataCollection = new ContextDataCollection($this);

        if (!empty($vars)) {
            $this->getResolver()->setRequired($vars);
        }

        foreach ($parameters as $key => $value) {
            $this->set($key, $value);
        }
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
                if (\is_object($value) && !$value instanceof ContextItemInterface) {
                    throw new InvalidOptionsException(sprintf(
                        'The option "%s" has invalid type. Expected "%s", but "%s" given.',
                        $name,
                        ContextItemInterface::class,
                        \get_class($value)
                    ));
                }
            }

            $this->resolved = true;
            $this->hash = $this->generateHash();
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
        return \array_key_exists($name, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!\array_key_exists($name, $this->items)) {
            throw new \OutOfBoundsException(sprintf('Undefined index: %s.', $name));
        }

        return $this->items[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getOr($name, $default = null)
    {
        return \array_key_exists($name, $this->items)
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
    public function offsetExists($name): bool
    {
        return \array_key_exists($name, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($name): mixed
    {
        if (!\array_key_exists($name, $this->items)) {
            throw new \OutOfBoundsException(sprintf('Undefined index: %s.', $name));
        }

        return $this->items[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($name, $value): void
    {
        $this->set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($name): void
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

    /**
     * {@inheritdoc}
     */
    public function getHash()
    {
        if (!$this->isResolved()) {
            throw new Exception\LogicException('The context is not resolved.');
        }

        return $this->hash;
    }

    /**
     * @return string
     */
    protected function generateHash()
    {
        $items = [];
        foreach ($this->items as $key => $item) {
            $items[$key] = $item instanceof ContextItemInterface
                ? $item->getHash()
                : $item;
        }

        $dataItems = [];
        $knownValues = $this->dataCollection->getKnownValues();
        foreach ($knownValues as $key) {
            if (!$this->dataCollection->has($key)) {
                continue;
            }

            $dataItem = $this->dataCollection->get($key);
            if ($dataItem instanceof ContextItemInterface) {
                $dataItems[$key] = $dataItem->getHash();
            } elseif (\is_scalar($dataItem) || \is_array($dataItem)) {
                try {
                    $dataItems[$key] = serialize($dataItem);
                } catch (\Exception $e) {
                    // Serialization of current data is not allowed
                }
            }
        }

        return md5(serialize($items) . serialize($dataItems));
    }
}
