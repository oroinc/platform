<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Provides a string representation of an entity identifier.
 */
class EntityIdAccessor
{
    /** @var ObjectPropertyAccessorInterface */
    private $propertyAccessor;

    /** @var EntityIdTransformerRegistry */
    private $entityIdTransformerRegistry;

    /**
     * @param ObjectPropertyAccessorInterface $propertyAccessor
     * @param EntityIdTransformerRegistry     $entityIdTransformerRegistry
     */
    public function __construct(
        ObjectPropertyAccessorInterface $propertyAccessor,
        EntityIdTransformerRegistry $entityIdTransformerRegistry
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    /**
     * Returns a string representation of the identifier of a given entity.
     *
     * @param mixed          $entity
     * @param EntityMetadata $metadata
     * @param RequestType    $requestType
     *
     * @return string
     */
    public function getEntityId($entity, EntityMetadata $metadata, RequestType $requestType): string
    {
        $result = null;

        $idFieldNames = $metadata->getIdentifierFieldNames();
        $idFieldNamesCount = \count($idFieldNames);

        if (null !== $entity && !\is_array($entity) && !\is_object($entity) && $idFieldNamesCount === 1) {
            $fieldName = \reset($idFieldNames);
            $entity = [$fieldName => $entity];
        }

        if ($idFieldNamesCount === 1) {
            $fieldName = \reset($idFieldNames);
            if (!$this->propertyAccessor->hasProperty($entity, $fieldName)) {
                throw new RuntimeException(
                    \sprintf(
                        'An object of the type "%s" does not have the identifier property "%s".',
                        $metadata->getClassName(),
                        $fieldName
                    )
                );
            }
            $result = $this->getEntityIdTransformer($requestType)->transform(
                $this->propertyAccessor->getValue($entity, $fieldName),
                $metadata
            );
        } elseif ($idFieldNamesCount > 1) {
            $id = [];
            foreach ($idFieldNames as $fieldName) {
                if (!$this->propertyAccessor->hasProperty($entity, $fieldName)) {
                    throw new RuntimeException(
                        \sprintf(
                            'An object of the type "%s" does not have the identifier property "%s".',
                            $metadata->getClassName(),
                            $fieldName
                        )
                    );
                }
                $id[$fieldName] = $this->propertyAccessor->getValue($entity, $fieldName);
            }
            $result = $this->getEntityIdTransformer($requestType)->transform($id, $metadata);
        } else {
            throw new RuntimeException(
                \sprintf(
                    'The "%s" entity does not have an identifier.',
                    $metadata->getClassName()
                )
            );
        }

        if (empty($result)) {
            throw new RuntimeException(
                \sprintf(
                    'The identifier value for "%s" entity must not be empty.',
                    $metadata->getClassName()
                )
            );
        }

        return $result;
    }

    /**
     * @param RequestType $requestType
     *
     * @return EntityIdTransformerInterface
     */
    private function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }
}
