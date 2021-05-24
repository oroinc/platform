<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Accessor;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

/**
 * Delegates accessing to entity data to child accessors.
 */
class DelegateAccessor implements AccessorInterface
{
    private iterable $accessors;
    private ?array $initializedAccessors = null;

    /**
     * @param iterable|AccessorInterface[] $accessors
     */
    public function __construct(iterable $accessors)
    {
        $this->accessors = $accessors;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'delegate';
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity, FieldMetadata $metadata)
    {
        return $this->findAccessor($entity, $metadata) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, FieldMetadata $metadata)
    {
        return $this->getAccessor($entity, $metadata)->getValue($entity, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($entity, FieldMetadata $metadata, $value)
    {
        $this->getAccessor($entity, $metadata)->setValue($entity, $metadata, $value);
    }

    /**
     * @param object        $entity
     * @param FieldMetadata $metadata
     *
     * @return AccessorInterface|null
     */
    private function findAccessor($entity, FieldMetadata $metadata): ?AccessorInterface
    {
        $accessors = $this->getAccessors();
        foreach ($accessors as $accessor) {
            if ($accessor->supports($entity, $metadata)) {
                return $accessor;
            }
        }

        return null;
    }

    /**
     * @param object        $entity
     * @param FieldMetadata $metadata
     *
     * @return AccessorInterface
     */
    private function getAccessor($entity, FieldMetadata $metadata): AccessorInterface
    {
        $accessor = $this->findAccessor($entity, $metadata);
        if (null === $accessor) {
            throw new InvalidArgumentException(
                sprintf('Cannot find accessor for "%s" field.', $metadata->getFieldName())
            );
        }

        return $accessor;
    }

    /**
     * @return AccessorInterface[]
     */
    private function getAccessors(): array
    {
        if (null === $this->initializedAccessors) {
            $initializedAccessors = [];
            /** @var AccessorInterface $accessor */
            foreach ($this->accessors as $accessor) {
                $initializedAccessors[$accessor->getName()] = $accessor;
            }
            $this->initializedAccessors = $initializedAccessors;
        }

        return $this->initializedAccessors;
    }
}
