<?php

namespace Oro\Component\EntitySerializer;

interface EntityFieldFilterInterface
{
    /**
     * @param string $className The FQCN of an entity
     * @param string $fieldName The name of an entity field
     *
     * @return bool
     */
    public function isApplicableField($className, $fieldName);
}
