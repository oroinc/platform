<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Oro\Component\PropertyAccess\PropertyAccessor;

class EntityCreationTransformer extends EntityToIdTransformer
{
    /**
     * Property of created entity that will be set with provided value
     *
     * @var string
     */
    protected $newEntityPropertyName;

    /**
     * Path where the value for new entity is stored in passed data
     *
     * @var string|null
     */
    protected $valuePath;

    /**
     * If true, allow to create new entity with empty data
     *
     * @var bool
     */
    protected $allowEmptyProperty = false;

    /**
     * @param string $newEntityPropertyName
     */
    public function setNewEntityPropertyName($newEntityPropertyName)
    {
        $this->newEntityPropertyName = $newEntityPropertyName;
    }

    /**
     * @param string $valuePath
     */
    public function setValuePath($valuePath)
    {
        $this->valuePath = $valuePath;
    }

    /**
     * @param bool $allowEmptyProperty
     */
    public function setAllowEmptyProperty($allowEmptyProperty)
    {
        $this->allowEmptyProperty = $allowEmptyProperty;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        } else {
            $data = $this->getData($value);
            $id = $this->propertyAccessor->getValue($data, $this->propertyPath);

            return $id ? parent::reverseTransform($id) : $this->createNewEntity($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createPropertyAccessor()
    {
        $this->propertyAccessor = new PropertyAccessor(false, true);
    }

    /**
     * @param array $data
     *
     * @return object
     */
    protected function createNewEntity(array $data)
    {
        if (!$this->valuePath && !$this->allowEmptyProperty) {
            throw new InvalidConfigurationException(
                'Property "valuePath" should be not empty or property "allowEmptyProperty" should be true.'
            );
        }
        $newEntityPropertyValue = $this->propertyAccessor->getValue($data, sprintf('[%s]', $this->valuePath));

        if (!$newEntityPropertyValue && !$this->allowEmptyProperty) {
            throw new InvalidConfigurationException(
                'No data provided for new entity property.'
            );
        }

        $object = new $this->className();
        if ($newEntityPropertyValue) {
            $this->propertyAccessor->setValue($object, $this->newEntityPropertyName, $newEntityPropertyValue);
        }

        return $object;
    }

    /**
     * @param array|int|string $value
     *
     * @return array
     */
    protected function getData($value)
    {
        // supported $value types:
        // json encoded array ['value' => $valueForPropertyOfNewEntity]
        // scalar types will be treated as ids
        if (!is_scalar($value) && !is_array($value)) {
            throw new UnexpectedTypeException($value, 'json encoded string, array or scalar value');
        }
        $data = is_scalar($value)
            ? json_decode($value, true)
            : $value;
        $data = is_array($data) ? $data : [$this->property => $value];

        return $data;
    }
}
