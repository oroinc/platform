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
        if ($doctrineMetadata) {
            $this->setDoctrineMetadata($doctrineMetadata);
        }
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
}
