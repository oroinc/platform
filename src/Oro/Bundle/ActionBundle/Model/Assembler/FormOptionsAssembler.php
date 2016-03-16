<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Form\Exception\InvalidConfigurationException;

use Oro\Bundle\ActionBundle\Exception\UnknownAttributeException;
use Oro\Bundle\ActionBundle\Model\Attribute;

class FormOptionsAssembler implements ConfigurationPassesAwareInterface
{
    use ConfigurationPassesAwareTrait;

    /**
     * @var Attribute[]
     */
    protected $attributes;

    /**
     * @param array $options
     * @param Attribute[]|Collection $attributes
     * @return array
     * @throws InvalidConfigurationException
     */
    public function assemble(array $options, $attributes)
    {
        $this->setAttributes($attributes);

        $attributeFields = array_key_exists('attribute_fields', $options)
            ? $options['attribute_fields']
            : [];

        $attributeFieldKeys = array_keys($attributeFields);
        foreach ($attributeFieldKeys as $attributeName) {
            $this->assertAttributeExists($attributeName);
        }

        $options['attribute_fields'] = $this->passConfiguration($attributeFields);

        if (!empty($options['attribute_default_values'])) {
            $value = $options['attribute_default_values'];

            $arrayKeys = array_keys($value);
            foreach ($arrayKeys as $attributeName) {
                $this->assertAttributeExists($attributeName);
                if (!array_key_exists($attributeName, $attributeFields)) {
                    throw new InvalidConfigurationException(
                        'Form options doesn\'t have attribute which is referenced in ' .
                        '"attribute_default_values" option.'
                    );
                }
            }
            $options['attribute_default_values'] = $this->passConfiguration($value);
        }

        return $options;
    }

    /**
     * @param Attribute[]|Collection $attributes
     * @return array
     */
    protected function setAttributes($attributes)
    {
        $this->attributes = [];
        if ($attributes) {
            foreach ($attributes as $attribute) {
                $this->attributes[$attribute->getName()] = $attribute;
            }
        }
    }

    /**
     * @param string $attributeName
     * @throws UnknownAttributeException
     */
    protected function assertAttributeExists($attributeName)
    {
        if (!isset($this->attributes[$attributeName])) {
            throw new UnknownAttributeException(
                sprintf('Unknown attribute "%s".', $attributeName)
            );
        }
    }
}
