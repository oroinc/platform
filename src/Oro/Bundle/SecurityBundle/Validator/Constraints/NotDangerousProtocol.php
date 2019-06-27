<?php

namespace Oro\Bundle\SecurityBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for checking if string contains a dangerous protocol.
 */
class NotDangerousProtocol extends Constraint
{
    public $validator = 'oro_security.validator.constraints.not_dangerous_protocol';
    public $message = 'oro.security.validator.not_dangerous_protocol.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return $this->validator;
    }
}
