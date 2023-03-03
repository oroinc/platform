<?php
declare(strict_types=1);

namespace Oro\Bundle\ActivityBundle\Model;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\EntityExtend\AssociationExtendEntity;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Extend activity trait.
 */
trait ExtendActivity
{
    /**
     * Checks if an entity of the given type can be associated with this activity entity
     *
     * @param string $targetClass The class name of the target entity
     * @return bool
     */
    public function supportActivityTarget($targetClass)
    {
        return AssociationExtendEntity::support($this, $targetClass);
    }

    /**
     * Gets entities associated with this activity entity
     *
     * @param string|null $targetClass The class name of the target entity
     * @return object[]
     */
    public function getActivityTargets($targetClass = null)
    {
        return AssociationExtendEntity::getTargets($this, $targetClass);
    }

    /**
     * Checks is the given entity is associated with this activity entity
     *
     * @param object $target Any configurable entity that can be associated with this activity
     *
     * @return bool
     */
    public function hasActivityTarget($target)
    {
        return AssociationExtendEntity::hasTarget($this, $target);
    }

    /**
     * Associates the given entity with this activity entity
     *
     * @param object $target Any configurable entity that can be associated with this activity
     * @return object This object
     */
    public function addActivityTarget($target)
    {
        AssociationExtendEntity::addTarget($this, $target);

        return $this;
    }

    /**
     * Removes the association of the given entity with this activity entity
     *
     * @param object $target Any configurable entity that can be associated with this activity
     * @return object This object
     */
    public function removeActivityTarget($target)
    {
        AssociationExtendEntity::removeTarget($this, $target);

        return $this;
    }

    public function getAssociationRelationType(): string
    {
        return RelationType::MANY_TO_MANY;
    }

    public function getAssociationRelationKind(): string
    {
        return ActivityScope::ASSOCIATION_KIND;
    }
}
