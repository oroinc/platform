<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Oro\Bundle\UIBundle\Provider\ObjectIdentityAccessorInterface;

class EntityIdentifierAccessor implements ObjectIdentityAccessorInterface
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
    public function getIdentifier($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }
}
