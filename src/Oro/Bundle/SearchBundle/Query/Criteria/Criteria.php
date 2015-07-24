<?php

namespace Oro\Bundle\SearchBundle\Query\Criteria;

use Doctrine\Common\Collections\Criteria as BaseCriteria;

class Criteria extends BaseCriteria
{
    /** @var ExpressionBuilder */
    private static $expressionBuilder;

    /**
     * {@inheritdoc}
     */
    public static function expr()
    {
        if (self::$expressionBuilder === null) {
            self::$expressionBuilder = new ExpressionBuilder();
        }

        return self::$expressionBuilder;
    }
}
