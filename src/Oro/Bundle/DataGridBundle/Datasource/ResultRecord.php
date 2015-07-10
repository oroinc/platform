<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Doctrine\Common\Inflector\Inflector;

class ResultRecord implements ResultRecordInterface
{
    /**
     * @var array
     */
    private $valueContainers = [];

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
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($this->valueContainers as $data) {
            if (is_array($data) && array_key_exists($name, $data)) {
                return $data[$name];
            }

            if (is_object($data)) {
                return $propertyAccessor->getValue($data, Inflector::camelize($name));
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
        if (array_key_exists(0, $this->valueContainers) && is_object($this->valueContainers[0])) {
            return $this->valueContainers[0];
        }

        return null;
    }
}
