<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Form\Handler;

use Oro\Bundle\UserBundle\Async\Topic\UserPasswordResetRequestTopic;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles forgot password request.
 */
class UserPasswordResetHandler extends AbstractPasswordResetRequestHandler
{
    public const string SESSION_PASSWORD_RESET_UNAVAILABLE = 'oro_user_password_reset_unavailable';
    public const string SESSION_PASSWORD_RESET_UNAVAILABLE_MESSAGE = 'oro_user_password_reset_unavailable_message';

    public function __construct(
        protected readonly UserManager $userManager,
        protected readonly TranslatorInterface $translator,
        LoggerInterface $logger,
        UserLoggingInfoProviderInterface $userLoggingInfoProvider,
        protected readonly int $ttl,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($logger);

        $this->setUserLoggingInfoProvider($userLoggingInfoProvider);
    }

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
