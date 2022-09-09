<?php

namespace Oro\Component\DoctrineUtils\DBAL\Schema;

use Doctrine\DBAL\Schema\AbstractAsset;

/**
 * DBAL Schema Model for Materialized View.
 *
 * @see https://www.postgresql.org/docs/13/rules-materializedviews.html
 */
class MaterializedView extends AbstractAsset
{
    /**
     * @var string A SQL query for this materialized view. A SELECT, TABLE, or VALUES command.
     */
    private string $definition;

    /**
     * @var bool Specifies whether the materialized view should be populated at creation time.
     */
    private bool $withData;

    /**
     * @param string $name Materialized view name
     * @param string $definition A SQL query for this materialized view. A SELECT, TABLE, or VALUES command.
     * @param bool $withData Specifies whether the materialized view should be populated at creation time.
     *                       False by default, which means that the materialized view will be in an unscannable state.
     */
    public function __construct(string $name, string $definition, bool $withData = false)
    {
        $this->_setName($name);
        $this->definition = $definition;
        $this->withData = $withData;
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }

    public function isWithData(): bool
    {
        return $this->withData;
    }

    public function __serialize(): array
    {
        return [$this->_name, $this->_namespace, $this->_quoted, $this->definition, $this->withData];
    }

    public function __unserialize(array $data): void
    {
        [$this->_name, $this->_namespace, $this->_quoted, $this->definition, $this->withData] = $data;
    }
}
