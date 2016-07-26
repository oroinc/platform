<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class FieldMetadata extends Metadata implements MetadataInterface
{
    /**
     * @var EntityMetadata
     */
    protected $entityMetadata;

    /**
     * @var DoctrineMetadata
     */
    protected $doctrineMetadata;

    /**
     * @param array $options
     * @param DoctrineMetadata $doctrineMetadata
     */
    public function __construct(array $options = [], DoctrineMetadata $doctrineMetadata = null)
    {
        parent::__construct($options);
        if ($doctrineMetadata) {
            $this->setDoctrineMetadata($doctrineMetadata);
        }
    }

    /**
     * @param EntityMetadata $entityMetadata
     */
    public function setEntityMetadata(EntityMetadata $entityMetadata)
    {
        $this->entityMetadata = $entityMetadata;
    }

    /**
     * @return EntityMetadata
     * @throws InvalidArgumentException
     */
    public function getEntityMetadata()
    {
        if (!$this->entityMetadata) {
            throw new InvalidArgumentException('Entity metadata is not configured.');
        }
        return $this->entityMetadata;
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
     * Checks if object has doctrine metadata
     *
     * @return bool
     */
    public function hasDoctrineMetadata()
    {
        return null !== $this->doctrineMetadata;
    }

    /**
     * Get field name
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public function getFieldName()
    {
        if (!$this->has('field_name')) {
            throw new InvalidArgumentException('Cannot get field name from merge field metadata.');
        }

        return $this->get('field_name');
    }

    /**
     * Get source field name
     *
     * @return string
     */
    public function getSourceFieldName()
    {
        if ($this->has('source_field_name')) {
            return $this->get('source_field_name');
        }

        return $this->getFieldName();
    }

    /**
     * Get source class name
     *
     * @return string
     */
    public function getSourceClassName()
    {
        if ($this->has('source_class_name')) {
            return $this->get('source_class_name');
        }

        return $this->getEntityMetadata()->getClassName();
    }

    /**
     * Get default merge mode
     *
     * @return array
     */
    public function getMergeMode()
    {
        $modes = $this->getMergeModes();
        return $modes ? reset($modes) : null;
    }

    /**
     * Get merge modes available
     *
     * @return array
     */
    public function getMergeModes()
    {
        return (array)$this->get('merge_modes');
    }

    /**
     * Add available merge mode.
     *
     * @param string $mergeMode
     * @return array
     */
    public function addMergeMode($mergeMode)
    {
        if (!$this->hasMergeMode($mergeMode)) {
            $mergeModes = $this->getMergeModes();

            //add to the front of the array, because target strategy must be the first
            array_unshift($mergeModes, $mergeMode);
            $this->set('merge_modes', array_unique($mergeModes));
        }
    }

    /**
     * Checks if merge mode is available
     *
     * @param string $mode
     * @return bool
     */
    public function hasMergeMode($mode)
    {
        return in_array($mode, $this->getMergeModes());
    }

    /**
     * Checks if field represents a collection
     *
     * @return bool
     */
    public function isCollection()
    {
        if (!$this->hasDoctrineMetadata()) {
            return $this->is('is_collection');
        }

        $doctrineMetadata = $this->getDoctrineMetadata();

        if (!$doctrineMetadata->isAssociation()) {
            return false;
        }

        if ($doctrineMetadata->isManyToMany()) {
            return true;
        }

        if ($this->isDefinedBySourceEntity()) {
            return $doctrineMetadata->isOneToMany();
        } else {
            return $doctrineMetadata->isManyToOne();
        }
    }

    /**
     * Checks if field defined by source entity
     *
     * @return bool
     */
    public function isDefinedBySourceEntity()
    {
        return $this->getSourceClassName() == $this->getEntityMetadata()->getClassName();
    }

    /**
     * Checks if field should be cloned
     *
     * @return bool
     */
    public function shouldBeCloned()
    {
        if (!$this->hasDoctrineMetadata()) {
            return false;
        }

        $doctrineMetadata = $this->getDoctrineMetadata();

        if (!$doctrineMetadata->isAssociation()) {
            return false;
        }

        return $doctrineMetadata->isOrphanRemoval() && $doctrineMetadata->isOneToMany();
    }
}
