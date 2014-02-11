<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class FieldMetadata extends Metadata implements FieldMetadataInterface
{
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
        $this->doctrineMetadata = $doctrineMetadata;
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
        if (!$this->getDoctrineMetadata()->has('fieldName')) {
            throw new InvalidArgumentException('Cannot get field name from merge field metadata.');
        }

        return $this->getDoctrineMetadata()->get('fieldName');
    }
}
