<?php

namespace Oro\Bundle\SearchBundle\Resolver;

interface EntityTitleResolverInterface
{
    /**
     * Resolve entity title
     *
     * @param  object $entity
     * @return string|null
     */
    public function resolve($entity);
}
