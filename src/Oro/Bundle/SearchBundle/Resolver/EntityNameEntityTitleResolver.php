<?php

namespace Oro\Bundle\SearchBundle\Resolver;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

class EntityNameEntityTitleResolver implements EntityTitleResolverInterface
{
    /** @var EntityTitleResolverInterface $resolver */
    protected $resolver;

    /** @var EntityNameResolver $entityNameResolver */
    protected $entityNameResolver;

    /**
     * @param EntityTitleResolverInterface $resolver
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(EntityTitleResolverInterface $resolver, EntityNameResolver $entityNameResolver)
    {
        $this->resolver = $resolver;
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($entity)
    {
        if ($title = $this->resolver->resolve($entity)) {
            return $title;
        }

        return $this->entityNameResolver->getName($entity);
    }
}
