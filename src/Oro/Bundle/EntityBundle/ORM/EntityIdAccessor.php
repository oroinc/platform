<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Oro\Bundle\UIBundle\Provider\ObjectIdAccessorInterface;

class EntityIdAccessor implements ObjectIdAccessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier($object)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($object);
    }
}
