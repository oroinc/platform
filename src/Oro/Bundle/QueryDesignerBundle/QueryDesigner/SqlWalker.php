<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\SqlWalker as GrandparentSqlWalker;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Oro\Component\DoctrineUtils\ORM\HookUnionTrait;

/**
 * Dynamicly applies limit to subquery which is "hooked" by SubQueryLimitHelper
 */
class SqlWalker extends TranslationWalker
{
    use HookUnionTrait;

    const WALKER_HOOK_LIMIT_KEY = 'walker_hook_for_limit';
    const WALKER_HOOK_LIMIT_VALUE = 'walker_hook_limit_value';
    const WALKER_HOOK_LIMIT_ID = 'walker_hook_limit_id';

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect)
    {
        $sql = parent::walkSubselect($subselect);
        $hookIdentifier = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_KEY);
        $limitValue = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_VALUE);
        $identifierField = $this->getQuery()->getHint(self::WALKER_HOOK_LIMIT_ID);

        if ($identifierField && $hookIdentifier && $limitValue && stripos($sql, $hookIdentifier) !== false) {
            // Remove hook condition from sql
            $sql = str_ireplace($hookIdentifier, '1<>0', $sql);
            $sql = "SELECT customTableAlias.$identifierField FROM ($sql LIMIT $limitValue) customTableAlias";
        }

        return $this->hookUnion($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutor($AST)
    {
        if ($AST instanceof SelectStatement) {
            return parent::getExecutor($AST);
        }

        return GrandparentSqlWalker::getExecutor($AST);
    }
}
