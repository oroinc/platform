<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class WorkflowEntity extends Constraint
{
    public $updateEntityMessage = 'oro.workflow.validator.entity.message.update';

    public $createFieldMessage = 'oro.workflow.validator.field.message.create';

    public $updateFieldMessage = 'oro.workflow.validator.field.message.update';

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
        return 'oro_workflow.validator.workflow_entity';
    }
}
