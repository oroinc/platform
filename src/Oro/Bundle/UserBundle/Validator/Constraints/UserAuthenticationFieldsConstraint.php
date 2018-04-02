<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Oro\Bundle\UserBundle\Validator\UserAuthenticationFieldsValidator;
use Symfony\Component\Validator\Constraint;

class UserAuthenticationFieldsConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.user.message.invalid_username';

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
        return UserAuthenticationFieldsValidator::ALIAS;
    }
}
