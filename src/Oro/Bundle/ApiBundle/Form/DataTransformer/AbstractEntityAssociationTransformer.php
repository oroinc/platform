<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Doctrine\ORM\ORMException;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * The base class for transformers for different kind of entity associations.
 */
abstract class AbstractEntityAssociationTransformer extends AbstractAssociationTransformer
{
    protected DoctrineHelper $doctrineHelper;
    protected EntityLoader $entityLoader;
    protected AssociationMetadata $metadata;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityLoader $entityLoader,
        AssociationMetadata $metadata
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityLoader = $entityLoader;
        $this->metadata = $metadata;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAcceptableEntityClassNames(): ?array
    {
        $acceptableClassNames = $this->metadata->getAcceptableTargetClassNames();
        if (!$acceptableClassNames && $this->metadata->isEmptyAcceptableTargetsAllowed()) {
            return null;
        }

        return $acceptableClassNames;
    }

    protected function loadEntity(string $entityClass, mixed $entityId): object
    {
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            throw FormUtil::createTransformationFailedException(
                sprintf('The "%s" class must be a managed Doctrine entity.', $entityClass),
                'oro.api.form.not_manageable_entity'
            );
        }
        $entity = null;
        try {
            $entity = $this->entityLoader->findEntity($entityClass, $entityId, $this->metadata->getTargetMetadata());
        } catch (ORMException $e) {
            throw new TransformationFailedException(
                sprintf(
                    'An "%s" entity with "%s" identifier cannot be loaded.',
                    $entityClass,
                    $this->humanizeEntityId($entityId)
                ),
                0,
                $e,
                'oro.api.form.load_entity_failed'
            );
        }
        if (null === $entity) {
            throw FormUtil::createTransformationFailedException(
                sprintf(
                    'An "%s" entity with "%s" identifier does not exist.',
                    $entityClass,
                    $this->humanizeEntityId($entityId)
                ),
                'oro.api.form.entity_does_not_exist'
            );
        }

        return $entity;
    }

    protected function resolveEntityClass(string $entityClass): string
    {
        $resolvedEntityClass = $this->doctrineHelper->resolveManageableEntityClass($entityClass);

        return $resolvedEntityClass ?? $entityClass;
    }

    protected function humanizeEntityId(mixed $entityId): string
    {
        if (\is_array($entityId)) {
            $elements = [];
            foreach ($entityId as $fieldName => $fieldValue) {
                $elements[] = sprintf('%s = %s', $fieldName, $fieldValue);
            }

            return sprintf('array(%s)', implode(', ', $elements));
        }

        return (string)$entityId;
    }
}
