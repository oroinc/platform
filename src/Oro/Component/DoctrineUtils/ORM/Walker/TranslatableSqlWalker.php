<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;

/**
 * Output Sql Walker decorator for TranslationWalker
 */
class TranslatableSqlWalker extends TranslationWalker
{
    use DecoratedSqlWalkerTrait;
}
