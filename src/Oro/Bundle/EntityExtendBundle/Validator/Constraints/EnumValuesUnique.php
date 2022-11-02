<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint that is used to check that enum options does not have duplicates.
 */
class EnumValuesUnique extends Constraint
{
    public $message = 'oro.entity_extend.enum.options_duplicates.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return EnumValuesUniqueValidator::ALIAS;
    }
}
