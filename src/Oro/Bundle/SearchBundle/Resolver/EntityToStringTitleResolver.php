<?php

namespace Oro\Bundle\SearchBundle\Resolver;

class EntityToStringTitleResolver implements EntityTitleResolverInterface
{
    /** @var EntityTitleResolverInterface $resolver */
    protected $resolver;

    /**
     * @param EntityTitleResolverInterface $resolver
     */
    public function __construct(EntityTitleResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($entity)
    {
        if ($title = $this->resolver->resolve($entity)) {
            return $title;
        }

        if (method_exists($entity, '__toString')) {
            return (string) $entity;
        }
    }
}
