<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\UserBundle\Async\Topic\UserPasswordResetRequestTopic;

/**
 * Handles forgot password request.
 */
class UserPasswordResetHandler extends AbstractPasswordResetRequestHandler
{
    #[\Override]
    protected function getFieldName(): string
    {
        return 'username';
    }

    #[\Override]
    protected function getTopicName(): string
    {
        return UserPasswordResetRequestTopic::getName();
    }
}
