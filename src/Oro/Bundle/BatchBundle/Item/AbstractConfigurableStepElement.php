<?php

namespace Oro\Bundle\BatchBundle\Item;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

/**
 * Define a configurable step element
 */
abstract class AbstractConfigurableStepElement
{
    /**
     * Return an array of fields for the configuration form
     */
    abstract public function getConfigurationFields(): array;

    public function getName(): string
    {
        $classname = get_class($this);

        if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
            $classname = $matches[1];
        }

        return Inflector::tableize($classname);
    }

    /**
     * Get the step element configuration (based on its properties)
     */
    public function getConfiguration(): array
    {
        $result = [];
        foreach (array_keys($this->getConfigurationFields()) as $field) {
            $result[$field] = $this->$field;
        }

        return $result;
    }

    /**
     * Set the step element configuration
     */
    public function setConfiguration(array $config): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($config as $key => $value) {
            if (array_key_exists($key, $this->getConfigurationFields())) {
                $accessor->setValue($this, $key, $value);
            }
        }
    }

    public function initialize(): void
    {
    }

    public function flush(): void
    {
    }
}
