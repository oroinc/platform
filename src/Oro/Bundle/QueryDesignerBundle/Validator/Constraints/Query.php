<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a datagrid query definition created by the query designer
 * is correct and can be executed.
 */
class Query extends Constraint
{
    /** @var string */
    public $message = 'oro.query_designer.invalid_query';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
