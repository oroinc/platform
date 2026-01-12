<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Provides Doctrine ORM metadata information for entity fields and associations.
 *
 * This class extends the base {@see Metadata} class to add Doctrine-specific functionality
 * for inspecting and working with Doctrine field and association mappings. It provides
 * methods to determine field types, check association relationships (one-to-many,
 * many-to-many, many-to-one, one-to-one), and inspect association properties like
 * orphan removal settings.
 */
class DoctrineMetadata extends Metadata implements MetadataInterface
{
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
        return $this->has('targetEntity');
    }

    /**
     * Checks if association type is equals to
     *
     * @param int $type
     * @return bool
     */
    public function isTypeEqual($type)
    {
        return $this->get('type') == $type;
    }

    /**
     * Checks if association type is ONE_TO_MANY
     *
     * @return bool
     */
    public function isOneToMany()
    {
        return $this->isTypeEqual(ClassMetadataInfo::ONE_TO_MANY);
    }

    /**
     * Checks if association type is MANY_TO_MANY
     *
     * @return bool
     */
    public function isManyToMany()
    {
        return $this->isTypeEqual(ClassMetadataInfo::MANY_TO_MANY);
    }

    /**
     * Checks if association type is MANY_TO_ONE
     *
     * @return bool
     */
    public function isManyToOne()
    {
        return $this->isTypeEqual(ClassMetadataInfo::MANY_TO_ONE);
    }

    /**
     * Checks if association type is ONE_TO_ONE
     *
     * @return bool
     */
    public function isOneToOne()
    {
        return $this->isTypeEqual(ClassMetadataInfo::ONE_TO_ONE);
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->get('fieldName', true);
    }

    /**
     * Checks if association has orphan removal enabled
     *
     * @return bool
     */
    public function isOrphanRemoval()
    {
        return $this->is('orphanRemoval');
    }
}
