<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class MultiEnumSnapshotField extends Constraint
{
    /** @var string */
    public $duplicateSnapshotMessage = 'This field name is duplicated snapshot of multi-select.';

    /** @var string */
    public $duplicateFieldMessage = 'This multi-select name is duplicated a field.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return MultiEnumSnapshotFieldValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
