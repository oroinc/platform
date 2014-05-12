<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class WorkflowData implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var bool
     */
    protected $modified;

    /**
     * @var array
     */
    protected $mapping;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * Constructor
     */
    public function __construct(array $data = array())
    {
        $this->data = $data;
        $this->modified = false;
        $this->mapping = array();
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Set mapping between fields.
     *
     * @param array $mapping
     * @return WorkflowData
     */
    public function setFieldsMapping(array $mapping)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * Get mapped field path by field name.
     *
     * @param string|PropertyPath $propertyPath
     * @return null|string
     */
    protected function getMappedPath($propertyPath)
    {
        if ($propertyPath instanceof PropertyPath) {
            return $propertyPath;
        }

        if (is_array($this->mapping) && array_key_exists($propertyPath, $this->mapping)) {
            $propertyPath = $this->mapping[$propertyPath];
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

        return new PropertyPath(join('.', $pathParts));
    }

    /**
     * @return bool
     */
    public function isModified()
    {
        return $this->modified;
    }

    /**
     * Set value
     *
     * @param string $name
     * @param mixed $value
     * @param boolean $changeModified
     * @return WorkflowData
     */
    public function set($name, $value, $changeModified = true)
    {
        try {
            $propertyPath = $this->getMappedPath($name);
            if ($changeModified && $this->propertyAccessor->getValue($this->data, $propertyPath) !== $value) {
                $this->modified = true;
            }
            $this->propertyAccessor->setValue($this->data, $propertyPath, $value);

            return $this;
        } catch (\Exception $e) {
            return $this;
        }
    }

    /**
     * Add values
     *
     * @param array $data
     * @return WorkflowData
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
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get data values
     *
     * @param array $names Optional list of names of values that should be filtered
     * @return array
     */
    public function getValues(array $names = array())
    {
        if (!$names) {
            return $this->data;
        }

        $result = array();

        /** @var Attribute $attribute */
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
                return null !== $this->propertyAccessor->getValue($this->data, $this->getMappedPath($name));
            } catch (NoSuchPropertyException $e) {
                return false;
            }
        }
    }

    /**
     * Remove value by name
     *
     * @param string $name
     * @return WorkflowData
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
        return empty($this->data);
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

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
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
