<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Entity creation data transformer.
 */
class EntityCreationTransformer extends EntityToIdTransformer
{
    /**
     * Property of created entity that will be set with provided value
     */
    protected string $newEntityPropertyName;

    /**
     * Path where the value for new entity is stored in passed data
     */
    protected ?string $valuePath = null;

    /**
     * If true, allow to create new entity with empty data
     */
    protected bool $allowEmptyProperty = false;

    public function setNewEntityPropertyName(string $newEntityPropertyName): void
    {
        $this->newEntityPropertyName = $newEntityPropertyName;
    }

    public function setValuePath(?string $valuePath): void
    {
        $this->valuePath = $valuePath;
    }

    public function setAllowEmptyProperty(bool $allowEmptyProperty): void
    {
        $this->allowEmptyProperty = $allowEmptyProperty;
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if (!$value) {
            return null;
        }

        $data = $this->getData($value);
        $id = null;
        if ($this->getPropertyAccessor()->isReadable($data, $this->getPropertyPath())) {
            $id = $this->getPropertyAccessor()->getValue($data, $this->getPropertyPath());
        }

        return $id ? parent::reverseTransform($id) : $this->createNewEntity($data);
    }

    protected function createNewEntity(array $data): object
    {
        if (!$this->valuePath && !$this->allowEmptyProperty) {
            throw new InvalidConfigurationException(
                'Property "valuePath" should be not empty or property "allowEmptyProperty" should be true.'
            );
        }
        $newEntityPropertyValue = $this->getPropertyAccessor()->getValue($data, \sprintf('[%s]', $this->valuePath));

        if (!$newEntityPropertyValue && !$this->allowEmptyProperty) {
            throw new InvalidConfigurationException('No data provided for new entity property.');
        }

        $object = new $this->className();
        if ($newEntityPropertyValue) {
            $this->getPropertyAccessor()->setValue($object, $this->newEntityPropertyName, $newEntityPropertyValue);
        }

        return $object;
    }

    protected function getData(mixed $value): array
    {
        // supported $value types:
        // json encoded array ['value' => $valueForPropertyOfNewEntity]
        // scalar types will be treated as ids
        if (!\is_scalar($value) && !\is_array($value)) {
            throw new UnexpectedTypeException($value, 'json encoded string, array or scalar value');
        }

        $data = \is_scalar($value)
            ? json_decode($value, true)
            : $value;
        if (!\is_array($data)) {
            $data = [$this->getProperty() => $value];
        }

        return $data;
    }
}
