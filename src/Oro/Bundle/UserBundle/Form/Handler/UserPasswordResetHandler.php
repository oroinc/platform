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

    protected ?EventDispatcherInterface $eventDispatcher = null;

    private UserManager $userManager;
    private TranslatorInterface $translator;
    private int $ttl;

    public function __construct(
        UserManager $userManager,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        UserLoggingInfoProviderInterface $userLoggingInfoProvider,
        int $ttl
    ) {
        parent::__construct($logger);

        $this->setUserLoggingInfoProvider($userLoggingInfoProvider);

        $this->userManager = $userManager;
        $this->translator = $translator;
        $this->ttl = $ttl;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
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
