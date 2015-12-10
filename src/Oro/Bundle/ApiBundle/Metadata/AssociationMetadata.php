<?php

namespace Oro\Bundle\ApiBundle\Metadata;

class AssociationMetadata extends PropertyMetadata
{
    /** FQCN of an association target */
    const TARGET_CLASS_NAME = 'targetClass';

    /** a flag indicates if an association represents "to-many" or "to-one" relation */
    const COLLECTION = 'collection';

    /** @var EntityMetadata|null */
    private $targetMetadata;

    /**
     * Gets metadata of an association target.
     *
     * @return EntityMetadata|null
     */
    public function getTargetMetadata()
    {
        return $this->targetMetadata;
    }

    /**
     * Sets metadata of an association target.
     *
     * @param EntityMetadata $targetMetadata
     */
    public function setTargetMetadata(EntityMetadata $targetMetadata)
    {
        $this->targetMetadata = $targetMetadata;
    }

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
     * Whether an association represents "to-many" or "to-one" relation.
     *
     * @return bool
     */
    public function isCollection()
    {
        return (bool)$this->get(self::COLLECTION);
    }

    /**
     * Sets a flag indicates whether an association represents "to-many" or "to-one" relation.
     *
     * @param bool $value TRUE for "to-many" relation, FALSE for "to-one" relation
     */
    public function setIsCollection($value)
    {
        $this->set(self::COLLECTION, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = parent::toArray();

        if (null !== $this->targetMetadata) {
            $result['targetMetadata'] = $this->targetMetadata->toArray();
        }

        return $result;
    }
}
