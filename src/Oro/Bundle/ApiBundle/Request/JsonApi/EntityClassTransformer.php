<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Request\EntityClassTransformerInterface;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class EntityClassTransformer implements EntityClassTransformerInterface
{
    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($entityClass)
    {
        return $this->entityAliasResolver->getPluralAlias($entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($entityType)
    {
        return $this->entityAliasResolver->getClassByPluralAlias($entityType);
    }
}
