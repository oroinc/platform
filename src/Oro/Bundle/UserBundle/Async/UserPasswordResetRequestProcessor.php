<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Async;

use Oro\Bundle\UserBundle\Async\Topic\AbstractPasswordResetRequestTopic;
use Oro\Bundle\UserBundle\Async\Topic\UserPasswordResetRequestTopic;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Event\PasswordChangeEvent;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Sends the reset password email for a forgot password request submitted in the back-office.
 */
class UserPasswordResetRequestProcessor extends AbstractPasswordResetRequestProcessor
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserLoggingInfoProviderInterface $userLoggingInfoProvider,
        LoggerInterface $logger,
        int $ttl
    ) {
        parent::__construct($logger, $ttl);
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [UserPasswordResetRequestTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $userIdentifier = $message->getBody()[AbstractPasswordResetRequestTopic::USER_IDENTIFIER];

        /** @var User|null $user */
        $user = $this->userManager->findUserByUsernameOrEmail($userIdentifier);
        if (!$user?->isEnabled()) {
            return self::ACK;
        }

        if (!$this->isPasswordResetAllowed($user) || $this->isPasswordAlreadyRequested($user)) {
            return self::ACK;
        }

        try {
            $this->userManager->sendResetPasswordEmail($user);
        } catch (\Exception $e) {
            $this->logger->error(
                'Unable to sent the reset password email.',
                ['email' => $user->getEmail(), 'exception' => $e]
            );

            return self::REJECT;
        }

        $this->logger->notice(
            'Reset password email has been sent',
            $this->getUserLoggingInfo($user)
        );

        $this->userManager->updateUser($user);

        return self::ACK;
    }

    #[\Override]
    protected function getUserLoggingInfo(AbstractUser $user): array
    {
        return $this->userLoggingInfoProvider->getUserLoggingInfo($user);
    }

    private function isPasswordResetAllowed(User $user): bool
    {
        $event = new PasswordChangeEvent($user);
        $this->eventDispatcher->dispatch($event, PasswordChangeEvent::BEFORE_PASSWORD_RESET);
        if ($event->isAllowed()) {
            return true;
        }

        $this->logger->notice(
            \sprintf(
                'Password reset request denied%s.',
                $event->getNotAllowedLogMessage() ? ' (' . $event->getNotAllowedLogMessage() . ')' : ''
            ),
            $this->getUserLoggingInfo($user)
        );

        return false;
    }
}
