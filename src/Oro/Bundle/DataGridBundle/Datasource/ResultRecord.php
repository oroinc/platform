<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Doctrine\Common\Inflector\Inflector;

class ResultRecord implements ResultRecordInterface
{
    /** @var array */
    private $valueContainers = [];

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /**
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->addData($data);
    }

    /**
     * @param array|object $data
     */
    public function addData($data)
    {
        if (is_array($data)) {
            $arrayData = [];
            foreach ($data as $name => $value) {
                if (is_numeric($name) && is_object($value)) {
                    $this->valueContainers[] = $value;
                } else {
                    $arrayData[$name] = $value;
                }
            }
            if ($arrayData) {
                array_unshift($this->valueContainers, $arrayData);
            }
        } elseif (is_object($data)) {
            $this->valueContainers[] = $data;
        }
    }

    /**
     * Get value of property by name
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function getValue($name)
    {
        foreach ($this->valueContainers as $data) {
            if (is_array($data)) {
                if (strpos($name, '[') === 0) {
                    return $this->getPropertyAccessor()->getValue($data, $name);
                } elseif (array_key_exists($name, $data)) {
                    return $data[$name];
                }
            } elseif (is_object($data)) {
                return $this->getPropertyAccessor()->getValue($data, Inflector::camelize($name));
            }
        }

        return null;
    }

    /**
     * Gets root entity from result record
     *
     * @return object|null
     */
    public function getRootEntity()
    {
        foreach ($this->valueContainers as $value) {
            if (is_object($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return PropertyAccessorInterface
     */
    protected function getPropertyAccessor()
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
