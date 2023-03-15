<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

/**
 * Entity Field Extensions Iterator
 */
class EntityFieldIterator implements EntityFieldIteratorInterface
{
    public function __construct(private iterable $extensions)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getExtensions(): iterable
    {
        return $this->extensions;
    }
}
