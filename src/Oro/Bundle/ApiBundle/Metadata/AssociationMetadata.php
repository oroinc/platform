<?php

namespace Oro\Bundle\ApiBundle\Metadata;

class AssociationMetadata extends PropertyMetadata
{
    /** FQCN of an association target */
    const TARGET_CLASS_NAME = 'targetClass';

    /** a flag indicates an association is collection valued */
    const COLLECTION_VALUED = 'collection';

    /**
     * Gets FQCN of an association target.
     *
     * @return string
     */
    public function getTargetClassName()
    {
        return $this->get(self::TARGET_CLASS_NAME);
    }

    /**
     * Sets FQCN of an association target.
     *
     * @param string $className
     */
    public function setTargetClassName($className)
    {
        $this->set(self::TARGET_CLASS_NAME, $className);
    }

    /**
     * Whether an association is single valued.
     *
     * @return bool
     */
    public function isSingleValued()
    {
        return !$this->isCollectionValued();
    }

    /**
     * Whether an association is collection valued.
     *
     * @return bool
     */
    public function isCollectionValued()
    {
        return $this->get(self::COLLECTION_VALUED);
    }

    /**
     * Sets a flag indicates an association is collection or single valued.
     *
     * @param bool $type FALSE for single valued association, TRUE for collection valued association
     */
    public function setCollectionValued($type)
    {
        $this->set(self::COLLECTION_VALUED, $type);
    }
}
