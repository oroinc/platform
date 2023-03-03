<?php

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

/**
 * Entity Field Extensions Iterator and Metadata provider interface
 */
interface EntityFieldIteratorInterface
{
    /**
     * @return EntityFieldExtensionInterface[]
     */
    public function getExtensions(): iterable;
}
