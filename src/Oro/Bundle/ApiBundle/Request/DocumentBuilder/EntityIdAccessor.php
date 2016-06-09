<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;

class EntityIdAccessor
{
    /** @var ObjectAccessorInterface */
    protected $objectAccessor;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /**
     * @param ObjectAccessorInterface      $objectAccessor
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(
        ObjectAccessorInterface $objectAccessor,
        EntityIdTransformerInterface $entityIdTransformer
    ) {
        $this->objectAccessor = $objectAccessor;
        $this->entityIdTransformer = $entityIdTransformer;
    }

    /**
     * Returns a string representation of the identifier of a given entity.
     *
     * @param mixed          $entity
     * @param EntityMetadata $metadata
     *
     * @return string
     */
    public function getEntityId($entity, EntityMetadata $metadata)
    {
        $result = null;

        $idFieldNames = $metadata->getIdentifierFieldNames();
        $idFieldNamesCount = count($idFieldNames);
        if ($idFieldNamesCount === 1) {
            $fieldName = reset($idFieldNames);
            if (!$this->objectAccessor->hasProperty($entity, $fieldName)) {
                throw new \RuntimeException(
                    sprintf(
                        'An object of the type "%s" does not have the identifier property "%s".',
                        $metadata->getClassName(),
                        $fieldName
                    )
                );
            }
            $result = $this->entityIdTransformer->transform(
                $this->objectAccessor->getValue($entity, $fieldName)
            );
        } elseif ($idFieldNamesCount > 1) {
            $id = [];
            foreach ($idFieldNames as $fieldName) {
                if (!$this->objectAccessor->hasProperty($entity, $fieldName)) {
                    throw new \RuntimeException(
                        sprintf(
                            'An object of the type "%s" does not have the identifier property "%s".',
                            $metadata->getClassName(),
                            $fieldName
                        )
                    );
                }
                $id[$fieldName] = $this->objectAccessor->getValue($entity, $fieldName);
            }
            $result = $this->entityIdTransformer->transform($id);
        } else {
            throw new \RuntimeException(
                sprintf(
                    'The "%s" entity does not have an identifier.',
                    $metadata->getClassName()
                )
            );
        }

        if (empty($result)) {
            throw new \RuntimeException(
                sprintf(
                    'The identifier value for "%s" entity must not be empty.',
                    $metadata->getClassName()
                )
            );
        }

        return $result;
    }
}
