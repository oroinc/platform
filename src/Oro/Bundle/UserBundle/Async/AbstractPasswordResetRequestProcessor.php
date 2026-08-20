<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Async;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Base processor for the forgot password requests.
 */
abstract class AbstractPasswordResetRequestProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly int $ttl
    ) {
    }

    /**
     * Prevents sending the reset password email more than once within the reset token lifetime.
     */
    protected function isPasswordAlreadyRequested(AbstractUser $user): bool
    {
        if (null === $user->getPasswordRequestedAt() || !$user->isPasswordRequestNonExpired($this->ttl)) {
            return false;
        }

        $this->logger->notice(
            \sprintf(
                'The password for this user has already been requested within the last %d hours.',
                $this->ttl / 3600
            ),
            $this->getUserLoggingInfo($user)
        );

        return true;
    }

    abstract protected function getUserLoggingInfo(AbstractUser $user): array;
}
