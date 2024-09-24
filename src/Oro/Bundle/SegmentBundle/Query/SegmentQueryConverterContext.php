<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryBuilderGroupingOrmQueryConverterContext;

/**
 * The context for {@see \Oro\Bundle\SegmentBundle\Query\SegmentQueryConverter}.
 */
class SegmentQueryConverterContext extends QueryBuilderGroupingOrmQueryConverterContext
{
    /** @var string */
    private $aliasPrefix;

    #[\Override]
    public function reset(): void
    {
        parent::reset();
        $this->aliasPrefix = null;
    }

    #[\Override]
    public function generateTableAlias(): string
    {
        $tableAlias = parent::generateTableAlias();
        if ($this->aliasPrefix) {
            $tableAlias .= '_' . $this->aliasPrefix;
        }

        return $tableAlias;
    }

    public function getAliasPrefix(): ?string
    {
        return $this->aliasPrefix;
    }

    public function setAliasPrefix(string $aliasPrefix): void
    {
        $this->aliasPrefix = $aliasPrefix;
    }
}
