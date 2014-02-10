<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineMetadata extends Metadata implements MetadataInterface
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @param string $className
     * @param array $options
     */
    public function __construct($className, array $options = [])
    {
        $this->className = $className;
        parent::__construct($options);
    }

    /**
     * Checks if this field represents simple doctrine field
     *
     * @return bool
     */
    public function isField()
    {
        return !$this->isAssociation();
    }

    /**
     * Checks if this field represents doctrine association
     *
     * @return bool
     */
    public function isAssociation()
    {
        return $this->has('targetEntity') && $this->has('joinColumns');
    }

    /**
     * Checks if this field represented by collections
     *
     * @return bool
     */
    public function isCollection()
    {
        $collectionTypes = [
            ClassMetadataInfo::ONE_TO_MANY,
            ClassMetadataInfo::MANY_TO_MANY,
        ];

        return $this->has('type') && in_array($this->get('type'), $collectionTypes);
    }

    /**
     * Checks if this metadata relates to field that is mapped in entity
     *
     * @return bool
     */
    public function isMappedBySourceEntity()
    {
        return $this->isField() || $this->className == $this->get('sourceEntity');
    }
}
