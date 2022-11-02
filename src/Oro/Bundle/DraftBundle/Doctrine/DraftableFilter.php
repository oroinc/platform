<?php

namespace Oro\Bundle\DraftBundle\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;

/**
 * All drafts should be removed from all queries
 */
class DraftableFilter extends SQLFilter
{
    public const FILTER_ID = 'draftable';

    /**
     * Gets the SQL query part to add to a query.
     *
     * @param ClassMetaData $targetEntity
     * @param string $targetTableAlias
     *
     * @return string The constraint SQL if there is available, empty string otherwise.
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!$this->isDraftableEntity($targetEntity)) {
            return '';
        }

        $platform = $this->getConnection()->getDatabasePlatform();

        return $platform->getIsNullExpression($targetTableAlias . '.' . $targetEntity->getColumnName('draftUuid'));
    }

    private function isDraftableEntity(ClassMetadata $targetEntity): bool
    {
        return $targetEntity->reflClass->implementsInterface(DraftableInterface::class)
            && $targetEntity->hasField('draftUuid');
    }
}
