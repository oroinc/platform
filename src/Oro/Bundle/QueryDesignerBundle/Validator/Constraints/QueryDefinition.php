<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a query definition created by the query designer
 * uses allowed entities and fields.
 */
class QueryDefinition extends Constraint
{
    /** @var string */
    public $message = 'oro.query_designer.entity.not_accessible';

    /** @var string */
    public $messageColumn = 'oro.query_designer.columns.not_accessible';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
