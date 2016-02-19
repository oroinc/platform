<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Request\EntityClassTransformerInterface;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
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
    public function transform($entityClass, $throwException = true)
    {
        try {
            return $this->entityAliasResolver->getPluralAlias($entityClass);
        } catch (EntityAliasNotFoundException $e) {
            if ($throwException) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($entityType, $throwException = true)
    {
        try {
            return $this->entityAliasResolver->getClassByPluralAlias($entityType);
        } catch (EntityAliasNotFoundException $e) {
            if ($throwException) {
                throw $e;
            }
        }

        return null;
    }
}
