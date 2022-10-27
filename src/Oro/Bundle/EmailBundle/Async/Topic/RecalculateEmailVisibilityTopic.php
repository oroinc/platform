<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Recalculate the visibility of email users where the email address is used.
 */
class RecalculateEmailVisibilityTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.email.recalculate_email_visibility';
    }

    public static function getDescription(): string
    {
        return 'Recalculate the visibility of email users where the email address is used';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['email'])
            ->addAllowedTypes('email', 'string')
            ->addAllowedValues('email', static function ($value) {
                if (!$value) {
                    throw new InvalidOptionsException('The "email" was expected to be not empty.');
                }

                return true;
            });
    }
}
