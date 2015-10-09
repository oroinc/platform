<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AutoResponseRuleCondition extends Constraint
{
    public $emptyInputMessage = 'Field cannot be empty.';
    public $nonEmptyInputMessage = 'Field must be empty.';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'Oro\Bundle\EmailBundle\Validator\AutoResponseRuleConditionValidator';
    }
}
