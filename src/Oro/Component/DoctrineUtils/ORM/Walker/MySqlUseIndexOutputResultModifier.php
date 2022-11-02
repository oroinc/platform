<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

use Doctrine\DBAL\Platforms\MySqlPlatform;

/**
 * Force MySQL to use given index.
 */
class MySqlUseIndexOutputResultModifier extends AbstractOutputResultModifier
{
    /**
     * @see https://dev.mysql.com/doc/refman/5.7/en/index-hints.html
     */
    public const HINT_USE_INDEX = 'oro.use_index';

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause, string $result)
    {
        if ($this->getConnection()->getDatabasePlatform() instanceof MySqlPlatform) {
            if ($index = $this->getQuery()->getHint(self::HINT_USE_INDEX)) {
                return preg_replace('/(\bFROM\s+\w+\s+\w+)/', '\1 USE INDEX (' . $index . ')', $result);
            }
        }

        return $result;
    }
}
