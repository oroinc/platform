<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\SqlWalker as GrandparentSqlWalker;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Oro\Component\DoctrineUtils\ORM\HookUnionTrait;

/**
 * Dynamically applies limit to sub-query which is "hooked" by SubQueryLimitHelper
 *
 * @deprecated use Oro\Component\DoctrineUtils\ORM\Walker\SqlWalker
 *             or Oro\Component\DoctrineUtils\ORM\Walker\Walker\TranslatableSqlWalker instead.
 *             SubQueryLimitOutputResultModifier is used by these walkers by default.
 */
class SqlWalker extends TranslationWalker
{
    use HookUnionTrait;

    const WALKER_HOOK_LIMIT_KEY = 'walker_hook_for_limit';
    const WALKER_HOOK_LIMIT_VALUE = 'walker_hook_limit_value';
    const WALKER_HOOK_LIMIT_ID = 'walker_hook_limit_id';

    /**
     * @var SubQueryLimitOutputResultModifier
     */
    private $outputResultModifier;

    /**
     * {@inheritDoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        parent::__construct($query, $parserResult, $queryComponents);
        $this->outputResultModifier = new SubQueryLimitOutputResultModifier($query, $parserResult, $queryComponents);
    }

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect)
    {
        $sql = parent::walkSubselect($subselect);

        $sql = $this->outputResultModifier->walkSubselect($subselect, $sql);

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
