<?php

namespace Oro\Bundle\EntityExtendBundle\Test;

use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldExtensionInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldIteratorInterface;

/**
 * This implementation of {@see EntityFieldIteratorInterface} is used in unit tests
 * to be able to add entity field extensions required for specific unit tests.
 */
class EntityFieldTestIterator implements EntityFieldIteratorInterface
{
    private array $extensions = [];

    /**
     * {@inheritDoc}
     */
    public function getExtensions(): iterable
    {
        return array_values($this->extensions);
    }

    public function addExtension(EntityFieldExtensionInterface $extension): void
    {
        $this->extensions[spl_object_id($extension)] = $extension;
    }

    public function removeExtension(EntityFieldExtensionInterface $extension): void
    {
        unset($this->extensions[spl_object_id($extension)]);
    }
}
