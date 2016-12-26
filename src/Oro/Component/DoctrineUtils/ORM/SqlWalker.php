<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\Query\AST\OrderByItem;
use Doctrine\ORM\Query\SqlWalker as BaseSqlWalker;

class SqlWalker extends BaseSqlWalker
{
    /**
     * @see https://dev.mysql.com/doc/refman/5.7/en/index-hints.html
     */
    const HINT_USE_INDEX = 'oro.use_index';

    public function walkFromClause($fromClause)
    {
        $result = parent::walkFromClause($fromClause);

        if ($this->getConnection()->getDatabasePlatform() instanceof MySqlPlatform) {
            return $this->walkMysqlFromResult($result);
        }

        return $result;
    }

    /**
     * @param string $result
     *
     * @return string
     */
    protected function walkMysqlFromResult($result)
    {
        if ($index = $this->getQuery()->getHint(self::HINT_USE_INDEX)) {
            $result = preg_replace('/(\bFROM\s+\w+\s+\w+)/', '\1 USE INDEX (' . $index . ')', $result);
        }

        return $result;
    }

    /**
     * Make sort strategy same for MySQL and PostgreSQL.
     *
     * ASC sorting should sort NULL to top, desc in reverse order.
     *
     * @param OrderByItem $orderByItem
     * {@inheritdoc}
     */
    public function walkOrderByItem($orderByItem)
    {
        $item = parent::walkOrderByItem($orderByItem);

        if ($this->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            if ($orderByItem->isDesc()) {
                $item .= ' NULLS LAST';
            } else {
                $item .= ' NULLS FIRST';
            }
        }

        return $item;
    }
}
