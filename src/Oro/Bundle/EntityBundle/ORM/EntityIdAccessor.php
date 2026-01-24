<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Oro\Bundle\UIBundle\Provider\ObjectIdAccessorInterface;

/**
 * Provides access to entity identifiers using Doctrine metadata.
 *
 * This accessor implements the {@see ObjectIdAccessorInterface} and uses DoctrineHelper
 * to retrieve the primary key value of any Doctrine-managed entity.
 */
class EntityIdAccessor implements ObjectIdAccessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function getIdentifier($object)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($object);
    }
}
