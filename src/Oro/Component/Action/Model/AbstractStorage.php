<?php

namespace Oro\Component\Action\Model;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\Proxy;
use Oro\Bundle\EntityExtendBundle\EntityExtend\PropertyAccessorWithDotArraySyntax;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Abstract implementation of workflow data storage.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractStorage implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var bool
     */
    protected $modified;

    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->modified = false;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax(
            throw: PropertyAccessorWithDotArraySyntax::THROW_ON_INVALID_INDEX
        );
    }

    /**
     * @return bool
     */
    public function isModified()
    {
        return $this->modified;
    }

    /**
     * This method should be called only by system listeners
     *
     * @param bool $modified
     * @return $this
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Set value
     *
     * @param string $name
     * @param mixed $value
     * @param bool $changeModified
     * @return $this
     */
    public function set($name, $value, $changeModified = true)
    {
        try {
            $propertyPath = $this->getMappedPath($name);
            if ($changeModified &&
                (!$this->has($name) || $this->propertyAccessor->getValue($this->data, $propertyPath) !== $value)
            ) {
                $this->modified = true;
            }
            $this->propertyAccessor->setValue($this->data, $propertyPath, $value);

            return $this;
        } catch (RuntimeException $e) {
            return $this;
        }
    }

    /**
     * Add values
     *
     * @param array $data
     * @return $this
     */
    public function add(array $data)
    {
        foreach ($data as $name => $value) {
            $this->set($name, $value);
        }

        return $this;
    }

    /**
     * Get value
     *
     * @param string $name
     * @return mixed $value
     */
    public function get($name)
    {
        try {
            $propertyPath = $this->getMappedPath($name);
            $value = $this->propertyAccessor->getValue($this->data, $propertyPath);
            if ($value instanceof Proxy && !$value->__isInitialized()) {
                // set value as null if entity is not exist
                $value = $this->extractProxyEntity($value);
                $this->set($name, $value);
            }

            return $value;
        } catch (RuntimeException $e) {
            return null;
        }
    }

    /**
     * Get data values
     *
     * @param array $names Optional list of names of values that should be filtered
     * @return array
     */
    public function getValues(array $names = [])
    {
        if (!$names) {
            return $this->data;
        }

        $result = [];

        foreach ($names as $name) {
            $result[$name] = $this->get($name);
        }

        return $result;
    }

    /**
     * Has value
     *
     * @param string $name
     * @return boolean
     */
    public function has($name)
    {
        if (array_key_exists($name, $this->data)) {
            return true;
        } else {
            try {
                $this->propertyAccessor->getValue($this->data, $this->getMappedPath($name));
            } catch (NoSuchPropertyException $e) {
                return false;
            }
            return true;
        }
    }

    /**
     * Remove value by name
     *
     * @param string $name
     * @return $this
     */
    public function remove($name)
    {
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
            $this->modified = true;
        }

        return $this;
    }

    /**
     * Is data empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return 0 === count($this->data);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        $this->remove($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Get mapped field path by field name.
     *
     * @param string|PropertyPathInterface $propertyPath
     * @return null|string
     */
    protected function getMappedPath($propertyPath)
    {
        if ($propertyPath instanceof PropertyPathInterface) {
            return $propertyPath;
        }

        return $this->getConstructedPropertyPath($propertyPath);
    }

    /**
     * Get property path for array as first accessor.
     *
     * @param string $path
     * @return string
     */
    protected function getConstructedPropertyPath($path)
    {
        $pathParts = explode('.', $path);
        $pathParts[0] = '[' . $pathParts[0] . ']';

        return new PropertyPath(implode('.', $pathParts));
    }

    /**
     * @param Proxy $entity
     * @return object|Proxy|null
     */
    protected function extractProxyEntity(Proxy $entity)
    {
        try {
            $entity->__load();
        } catch (EntityNotFoundException $exception) {
            $entity = null;
        }

        return $entity;
    }
}
