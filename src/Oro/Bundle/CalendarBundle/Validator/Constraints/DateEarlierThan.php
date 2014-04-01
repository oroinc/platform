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
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'field';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return array('field');
    }
}
