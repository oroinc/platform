<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActionBundle\Exception\AssemblerException;
use Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;

class AttributeAssembler extends AbstractAssembler
{
    const DEFAULT_ENTITY_ATTRIBUTE = 'entity';

    /**
     * @var AttributeGuesser
     */
    protected $attributeGuesser;

    /**
     * @param AttributeGuesser $attributeGuesser
     */
    public function __construct(AttributeGuesser $attributeGuesser)
    {
        $this->attributeGuesser = $attributeGuesser;
    }

    /**
     * @param ActionData $data
     * @param array $configuration
     * @return ArrayCollection
     * @throws AssemblerException If configuration is invalid
     */
    public function assemble(ActionData $data, array $configuration)
    {
        $entityAttributeName = static::DEFAULT_ENTITY_ATTRIBUTE;
        if (!array_key_exists($entityAttributeName, $configuration)) {
            $configuration[$entityAttributeName] = [
                'label' => $entityAttributeName,
                'type' => 'entity',
                'options' => [
                    'class' => ClassUtils::getClass($data->getEntity()),
                ]
            ];
        }

        $attributes = new ArrayCollection();
        foreach ($configuration as $name => $options) {
            $attribute = $this->assembleAttribute($data, $name, $options);
            $attributes->set($name, $attribute);
        }

        return $attributes;
    }

    /**
     * @param ActionData $data
     * @param string $name
     * @param array $options
     * @return Attribute
     */
    protected function assembleAttribute(ActionData $data, $name, array $options = [])
    {
        if (!empty($options['property_path'])) {
            $options = $this->guessOptions(
                $options,
                ClassUtils::getClass($data->getEntity()),
                $options['property_path']
            );
        }

        $this->assertOptions($options, ['label', 'type'], 'attributes.' . $name);
        $this->assertAttributeEntityAcl($options);

        $attribute = new Attribute();
        $attribute->setName($name);
        $attribute->setLabel($options['label']);
        $attribute->setType($options['type']);
        $attribute->setEntityAcl($this->getOption($options, 'entity_acl', []));
        $attribute->setPropertyPath($this->getOption($options, 'property_path'));
        $attribute->setOptions($this->getOption($options, 'options', []));

        $this->validateAttribute($attribute);

        return $attribute;
    }

    /**
     * @param array $options
     * @param string $rootClass
     * @param string $propertyPath
     * @return array
     */
    protected function guessOptions(array $options, $rootClass, $propertyPath)
    {
        $guessedOptions = ['label', 'type', 'options'];
        $needGuess = false;
        foreach ($guessedOptions as $option) {
            if (empty($options[$option])) {
                $needGuess = true;
                break;
            }
        }

        if (!$needGuess) {
            return $options;
        }

        $attributeParameters = $this->attributeGuesser->guessParameters($rootClass, $propertyPath);
        if ($attributeParameters) {
            foreach ($guessedOptions as $option) {
                if (empty($options[$option]) && !empty($attributeParameters[$option])) {
                    $options[$option] = $attributeParameters[$option];
                }
            }
        }

        return $options;
    }

    /**
     * @param array $options
     * @throws AssemblerException
     */
    protected function assertAttributeEntityAcl(array $options)
    {
        if ($options['type'] !== 'entity' && array_key_exists('entity_acl', $options)) {
            throw new AssemblerException(
                sprintf(
                    'Attribute "%s" with type "%s" can\'t have entity ACL',
                    $options['label'],
                    $options['type']
                )
            );
        }
    }

    /**
     * @param Attribute $attribute
     * @throws AssemblerException If attribute is invalid
     */
    protected function validateAttribute(Attribute $attribute)
    {
        $this->assertAttributeHasValidType($attribute);

        if (in_array($attribute->getType(), ['object', 'entity'], true)) {
            $this->assertAttributeHasClassOption($attribute);
        } else {
            $this->assertAttributeHasNoOptions($attribute, 'class');
        }
    }

    /**
     * @param Attribute $attribute
     * @throws AssemblerException If attribute is invalid
     */
    protected function assertAttributeHasValidType(Attribute $attribute)
    {
        $attributeType = $attribute->getType();
        $allowedTypes = ['bool', 'boolean', 'int', 'integer', 'float', 'string', 'array', 'object', 'entity'];

        if (!in_array($attributeType, $allowedTypes, true)) {
            throw new AssemblerException(
                sprintf(
                    'Invalid attribute type "%s", allowed types are "%s"',
                    $attributeType,
                    implode('", "', $allowedTypes)
                )
            );
        }
    }

    /**
     * @param Attribute $attribute
     * @param string|array $optionNames
     * @throws MissedRequiredOptionException If attribute is invalid
     */
    protected function assertAttributeHasOptions(Attribute $attribute, $optionNames)
    {
        $optionNames = (array)$optionNames;

        foreach ($optionNames as $optionName) {
            if (!$attribute->hasOption($optionName)) {
                throw new MissedRequiredOptionException(
                    sprintf('Option "%s" is required in attribute "%s"', $optionName, $attribute->getName())
                );
            }
        }
    }

    /**
     * @param Attribute $attribute
     * @param string|array $optionNames
     * @throws AssemblerException If attribute is invalid
     */
    protected function assertAttributeHasNoOptions(Attribute $attribute, $optionNames)
    {
        $optionNames = (array)$optionNames;

        foreach ($optionNames as $optionName) {
            if ($attribute->hasOption($optionName)) {
                throw new AssemblerException(
                    sprintf('Option "%s" cannot be used in attribute "%s"', $optionName, $attribute->getName())
                );
            }
        }
    }

    /**
     * @param Attribute $attribute
     * @throws AssemblerException If attribute is invalid
     */
    protected function assertAttributeHasClassOption(Attribute $attribute)
    {
        $this->assertAttributeHasOptions($attribute, 'class');
        if (!class_exists($attribute->getOption('class'))) {
            throw new AssemblerException(
                sprintf(
                    'Class "%s" referenced by "class" option in attribute "%s" not found',
                    $attribute->getOption('class'),
                    $attribute->getName()
                )
            );
        }
    }
}
