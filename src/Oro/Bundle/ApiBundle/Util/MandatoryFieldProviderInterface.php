<?php

namespace Oro\Bundle\ApiBundle\Util;

/**
 * The interface for classes that can provide the list of fields
 * that should be always included in SELECT clause.
 */
interface MandatoryFieldProviderInterface
{
    /**
     * Gets the list of mandatory fields for the given entity.
     *
     * @param string $entityClass
     *
     * @return string[]
     */
    public function getMandatoryFields(string $entityClass): array;
}
