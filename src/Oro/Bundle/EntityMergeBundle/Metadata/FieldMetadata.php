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
    public function __construct(array $options, DoctrineMetadata $doctrineMetadata = null)
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
     */
    public function getEntityMetadata()
    {
        return $this->entityMetadata;
    }

    /**
     * @param DoctrineMetadata $doctrineMetadata
     * @return DoctrineMetadata
     */
    public function setDoctrineMetadata(DoctrineMetadata $doctrineMetadata)
    {
        $this->doctrineMetadata = $doctrineMetadata;
        $this->set('field_name', $doctrineMetadata->get('fieldName'));
    }

    /**
     * @return DoctrineMetadata
     */
    public function getDoctrineMetadata()
    {
        return $this->doctrineMetadata;
    }

    /**
     * @return bool
     */
    public function hasDoctrineMetadata()
    {
        return null !== $this->doctrineMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldName()
    {
        if (!$this->has('field_name')) {
            throw new InvalidArgumentException('Cannot get field name from merge field metadata.');
        }

        return $this->get('field_name');
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
            $mergeModes[] = $mergeMode;
            $this->set('merge_modes', $mergeModes);
        }
    }

    /**
     * Checks if merge mode is available
     *
     * @param string $mode
     * @return array
     */
    public function hasMergeMode($mode)
    {
        return in_array($mode, $this->getMergeModes());
    }

    /**
     * @return bool
     */
    public function isCollection()
    {
        if ($this->has('is_collection')) {
            return (bool)$this->get('is_collection');
        }
        if ($this->hasDoctrineMetadata()) {
            return $this->getDoctrineMetadata()->isCollection();
        }
        return false;
    }
}
