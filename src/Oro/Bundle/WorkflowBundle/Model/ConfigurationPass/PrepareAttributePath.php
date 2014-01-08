<?php

namespace Oro\Bundle\WorkflowBundle\Model\ConfigurationPass;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\AttributesAwareInterface;

/**
 * Passes through configuration array and replaces parameter strings ($parameter.name)
 * for attributes with real paths declared in attribute definition
 */
class PrepareAttributePath implements ConfigurationPassInterface, AttributesAwareInterface
{
    /**
     * @var Collection
     */
    protected $attributes;

    /**
     * {@inheritdoc}
     */
    public function setAttributes(Collection $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function passConfiguration(array $configuration)
    {
        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->passConfiguration($value);
            } elseif ($this->isStringPropertyPath($value)) {
                $data[$key] = $this->replaceAttribute($value);
            }
        }

        return $configuration;
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isStringPropertyPath($path)
    {
        return is_string($path) && strpos($path, '$') === 0;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function replaceAttribute($value)
    {
        $property = substr($value, 1);

        if (0 !== strpos($property, '.')) {
            $pathParts = explode('.', $property);
            $attribute = $this->getAttributeByName($pathParts[0]);
            $attributeRealPath = $attribute->getPropertyPath();
            if ($attributeRealPath) {
                $pathParts[0] = $attributeRealPath;
                $value = '$' . implode('.', $pathParts);
            }
        }

        return $value;
    }

    /**
     * @param string $name
     * @return Attribute
     */
    protected function getAttributeByName($name)
    {
        return $this->attributes->get($name);
    }
}
