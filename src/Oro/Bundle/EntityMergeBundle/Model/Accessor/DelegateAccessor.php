<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Accessor;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class DelegateAccessor implements AccessorInterface
{
    /**
     * @var AccessorInterface[]
     */
    protected $elements;

    /**
     * @param array $accessors
     */
    public function __construct(array $accessors = array())
    {
        $this->elements = array();

        foreach ($accessors as $accessor) {
            $this->add($accessor);
        }
    }

    /**
     * @param AccessorInterface $accessor
     * @throws InvalidArgumentException
     */
    public function add(AccessorInterface $accessor)
    {
        if ($accessor === $this) {
            throw new InvalidArgumentException("Cannot add accessor to itself.");
        }
        $this->elements[$accessor->getName()] = $accessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, FieldMetadata $metadata)
    {
        return $this->match($entity, $metadata, true)->getValue($entity, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($entity, FieldMetadata $metadata, $value)
    {
        $this->match($entity, $metadata, true)->setValue($entity, $metadata, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'delegate';
    }

    /**
     * Checks if this class supports accessing entity
     *
     * @param string        $entity
     * @param FieldMetadata $metadata
     * @return string
     */
    public function supports($entity, FieldMetadata $metadata)
    {
        return $this->match($entity, $metadata, false) !== null;
    }

    /**
     * Match field data and field merger
     *
     * @param object        $entity
     * @param FieldMetadata $metadata
     * @param bool          $throwException
     * @return AccessorInterface|null
     * @throws InvalidArgumentException
     */
    protected function match($entity, FieldMetadata $metadata, $throwException)
    {
        foreach ($this->elements as $accessor) {
            if ($accessor->supports($entity, $metadata)) {
                return $accessor;
            }
        }
        if ($throwException) {
            throw new InvalidArgumentException(
                sprintf('Cannot find accessor for "%s" field.', $metadata->getFieldName())
            );
        }
        return null;
    }
}
