<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class MetadataFactory
{
    /**
     * @param array $options
     * @param DoctrineMetadata|array $doctrineMetadata
     * @return EntityMetadata
     */
    public function createEntityMetadata(array $options = array(), $doctrineMetadata = null)
    {
        if ($doctrineMetadata) {
            $doctrineMetadata = $this->getDoctrineMetadataOrCreateFromArray($doctrineMetadata);
        }
        return new EntityMetadata($options, $doctrineMetadata);
    }

    /**
     * @param array $options
     * @param DoctrineMetadata|array $doctrineMetadata
     * @return FieldMetadata
     */
    public function createFieldMetadata(array $options = array(), $doctrineMetadata = null)
    {
        if ($doctrineMetadata) {
            $doctrineMetadata = $this->getDoctrineMetadataOrCreateFromArray($doctrineMetadata);
        }
        return new FieldMetadata($options, $doctrineMetadata);
    }

    /**
     * @param array $mapping
     * @return DoctrineMetadata
     */
    public function createDoctrineMetadata(array $mapping = array())
    {
        return new DoctrineMetadata($mapping);
    }

    /**
     * @param DoctrineMetadata|array $doctrineMetadata
     * @return DoctrineMetadata
     * @throws InvalidArgumentException
     */
    protected function getDoctrineMetadataOrCreateFromArray($doctrineMetadata)
    {
        if (is_array($doctrineMetadata)) {
            $doctrineMetadata = $this->createDoctrineMetadata($doctrineMetadata);
        } elseif (!$doctrineMetadata instanceof DoctrineMetadata) {
            throw new InvalidArgumentException(
                sprintf(
                    '$doctrineMetadata must be an array of "%s", but "%s" given.',
                    'Oro\\Bundle\\EntityMergeBundle\\Metadata\\DoctrineMetadata',
                    is_object($doctrineMetadata) ? get_class($doctrineMetadata) : gettype($doctrineMetadata)
                )
            );
        }
        return $doctrineMetadata;
    }
}
