<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Recalculate the visibility of email users where the email address is used.
 */
class RecalculateEmailVisibilityTopic extends AbstractTopic implements JobAwareTopicInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.email.recalculate_email_visibility';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Recalculate the visibility of email users where the email address is used';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['email'])
            ->addAllowedTypes('email', ['string[]', 'string'])
            ->addAllowedValues('email', static function ($value) {
                if (!$value) {
                    throw new InvalidOptionsException('At least one email should be provided.');
                }

                return true;
            });
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        $emailAddress = $messageBody['email'];
        if (!\is_array($emailAddress)) {
            $emailAddress = [$emailAddress];
        }

        return sprintf('%s:%s', self::getName(), md5(implode(',', $emailAddress)));
    }
}
