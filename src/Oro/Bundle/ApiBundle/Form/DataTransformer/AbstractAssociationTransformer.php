<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * The base class for transformers for different kind of associations.
 */
abstract class AbstractAssociationTransformer implements DataTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }
        if (empty($value)) {
            return null;
        }

        if (empty($value['class'])) {
            throw FormUtil::createTransformationFailedException(
                'Expected an array with "class" element.',
                'oro.api.form.invalid_entity_type'
            );
        }

        $entityClass = $value['class'];
        $acceptableClassNames = $this->getAcceptableEntityClassNames();
        if ($acceptableClassNames) {
            if (!\in_array($entityClass, $acceptableClassNames, true)) {
                throw FormUtil::createTransformationFailedException(
                    sprintf(
                        'The "%s" class is not acceptable. Acceptable classes: %s.',
                        $entityClass,
                        implode(',', $acceptableClassNames)
                    ),
                    'oro.api.form.not_acceptable_entity'
                );
            }
        } elseif (null !== $acceptableClassNames) {
            throw FormUtil::createTransformationFailedException(
                'There are no acceptable classes.',
                'oro.api.form.no_acceptable_entities'
            );
        }

        if (!\array_key_exists('id', $value)) {
            throw new TransformationFailedException('Expected an array with "id" element.');
        }

        if (!$this->isEntityIdAcceptable($value['id'])) {
            throw FormUtil::createTransformationFailedException(
                'The "id" element is expected to be an integer, non-empty string or non-empty array.',
                'oro.api.form.invalid_entity_id'
            );
        }

        return $this->getEntity($entityClass, $value['id']);
    }

    protected function isEntityIdAcceptable(mixed $entityId): bool
    {
        return \is_int($entityId)
            || (\is_string($entityId) && '' !== trim($entityId))
            || (\is_array($entityId) && \count($entityId));
    }

    abstract protected function getEntity(string $entityClass, mixed $entityId): mixed;

    /**
     * @return string[]|null class names of acceptable entities or NULL if any entities can be accepted
     */
    abstract protected function getAcceptableEntityClassNames(): ?array;
}
