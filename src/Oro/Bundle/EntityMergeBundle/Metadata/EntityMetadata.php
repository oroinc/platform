<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class EntityMetadata extends Metadata implements MetadataInterface
{
    const MAX_ENTITIES_COUNT = 5;

    /**
     * @var DoctrineMetadata
     */
    protected $doctrineMetadata;

    /**
     * @var FieldMetadata[]
     */
    protected $fieldsMetadata = array();

    /**
     * @param array $options
     * @param DoctrineMetadata $doctrineMetadata
     */
    public function __construct(array $options = array(), DoctrineMetadata $doctrineMetadata = null)
    {
        parent::__construct($options);
        if ($doctrineMetadata) {
            $this->setDoctrineMetadata($doctrineMetadata);
        }
    }

    /**
     * @param FieldMetadata $fieldMetadata
     */
    public function addFieldMetadata(FieldMetadata $fieldMetadata)
    {
        $fieldMetadata->setEntityMetadata($this);
        $this->fieldsMetadata[$fieldMetadata->getFieldName()] = $fieldMetadata;
    }

    /**
     * @param DoctrineMetadata $doctrineMetadata
     * @return DoctrineMetadata
     */
    public function setDoctrineMetadata(DoctrineMetadata $doctrineMetadata)
    {
        $this->doctrineMetadata = $doctrineMetadata;
    }

    /**
     * @return DoctrineMetadata
     * @throws InvalidArgumentException
     */
    public function getDoctrineMetadata()
    {
        if (!$this->doctrineMetadata) {
            throw new InvalidArgumentException('Doctrine metadata is not configured.');
        }
        return $this->doctrineMetadata;
    }

    /**
     * Get list of fields metadata
     *
     * @return FieldMetadata[]
     */
    public function getFieldsMetadata()
    {
        return $this->fieldsMetadata;
    }

    /**
     * Get entity class name
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public function getClassName()
    {
        if (!$this->getDoctrineMetadata()->has('name')) {
            throw new InvalidArgumentException('Cannot get class name from merge entity metadata.');
        }

        return $this->getDoctrineMetadata()->get('name');
    }

    /**
     * @return mixed
     */
    public function getMaxEntitiesCount()
    {
        if ($this->has('max_entities_count')) {
            return $this->get('max_entities_count');
        }

        return self::MAX_ENTITIES_COUNT;
    }
}
