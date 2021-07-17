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

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        parent::reset();
        $this->aliasPrefix = null;
    }

    /**
     * {@inheritDoc}
     */
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
