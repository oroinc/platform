<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint checking if entity can be changed taking into account workflow.
 */
class WorkflowEntity extends Constraint
{
    public string $updateEntityMessage = 'oro.workflow.validator.entity.message.update';
    public string $createFieldMessage = 'oro.workflow.validator.field.message.create';
    public string $updateFieldMessage = 'oro.workflow.validator.field.message.update';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
