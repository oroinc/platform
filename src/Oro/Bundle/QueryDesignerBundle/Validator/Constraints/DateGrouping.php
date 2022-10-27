<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a query definition created by the query designer
 * has valid configuration of the grouping by date section.
 */
class DateGrouping extends Constraint
{
    /** @var string */
    public $groupByMandatoryMessage = 'oro.query_designer.date_grouping.group_by.not_blank';

    /** @var string */
    public $dateFieldMandatoryMessage = 'oro.query_designer.date_grouping.date_field.not_blank';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
