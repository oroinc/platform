<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PasswordAlreadyUsed extends Constraint
{
    /** @var string */
    public $message = 'oro.user.message.password_already_used';

    /** @var int */
    public $userId;

    public function __construct($options = null)
    {
        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'userId';
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_used_password';
    }
}
