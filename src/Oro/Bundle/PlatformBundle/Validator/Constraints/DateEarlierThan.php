<?php

namespace Oro\Bundle\PlatformBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DateEarlierThan extends Constraint
{
    public $field;

    public $message = "This date should be earlier than {{ field }} date";

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
        return ['field'];
    }
}
