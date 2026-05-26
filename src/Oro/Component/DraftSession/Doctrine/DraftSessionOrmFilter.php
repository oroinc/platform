<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Excludes draft entities from queries by checking that the draftSessionUuid field is NULL.
 * Applies to any entity that declares a draftSessionUuid mapped field.
 */
class DraftSessionOrmFilter extends SQLFilter
{
    #[\Override]
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (!$targetEntity->hasField('draftSessionUuid')) {
            return '';
        }

        return $this->getConnection()->getDatabasePlatform()->getIsNullExpression(
            $targetTableAlias . '.' . $targetEntity->getColumnName('draftSessionUuid')
        );
    }
}
