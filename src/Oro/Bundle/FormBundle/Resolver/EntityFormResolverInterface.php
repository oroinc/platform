<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Resolver;

/**
 * Submits form to get filled up new entity based on provided form data and form type class.
 */
interface EntityFormResolverInterface
{
    public function resolve(string $formTypeClass, object $entity, array $entityData): object;
}
