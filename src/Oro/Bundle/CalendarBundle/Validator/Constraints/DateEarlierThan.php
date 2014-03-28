<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DateEarlierThan extends Constraint
{
    public $field;
    public $message = 'This field can not be earlier than {{ field }} field';

    /**
     * {@inheritDoc}
     */
    public function getDefaultOption()
    {
        return 'field';
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredOptions()
    {
        return array('field');
    }
}