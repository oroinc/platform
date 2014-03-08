<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator as BaseGenerator;

class DbIdentifierNameGenerator extends BaseGenerator
{
    /**
     * Builds a table name for many-to-many relation
     *
     * @param string $entityClassName
     * @param string $fieldName
     * @param string $targetEntityClassName
     * @return string
     */
    public function generateManyToManyJoinTableName($entityClassName, $fieldName, $targetEntityClassName)
    {
        $selfParts       = explode('\\', $entityClassName);
        $vendorName      = strpos($entityClassName, ExtendConfigDumper::ENTITY) === 0
            ? 'oro'
            : array_shift($selfParts);
        $entityClassName = array_pop($selfParts);

        $targetParts           = explode('\\', $targetEntityClassName);
        $targetEntityClassName = array_pop($targetParts);

        return $this->generateIdentifierName([$fieldName], $vendorName, [$entityClassName, $targetEntityClassName]);
    }
}
