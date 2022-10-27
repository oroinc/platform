<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Util\JSON;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validation;

/**
 * Send localized emails to specified recipients using specified email template.
 */
class SendEmailTemplateTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.email.send_email_template';
    }

    public static function getDescription(): string
    {
        return 'Send localized emails to specified recipients using specified email template.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'from',
                'templateName',
                'recipients',
                'entity',
            ])
            ->addAllowedTypes('from', 'string')
            ->addAllowedTypes('recipients', 'string[]')
            ->addAllowedTypes('templateName', 'string')
            ->addAllowedTypes('entity', 'array')
            ->addAllowedValues('entity', function ($value) {
                if (count($value) !== 2) {
                    throw new InvalidOptionsException(
                        sprintf(
                            'Parameter "entity" must be an array [string $entityClass, int $entityId], got "%s".',
                            JSON::encode($value)
                        )
                    );
                }

                return true;
            })
            ->addAllowedValues('from', Validation::createIsValidCallable(new EmailConstraint()))
            ->addAllowedValues('recipients', static function ($recipients) {
                if (!$recipients) {
                    throw new InvalidOptionsException('Parameter "recipients" must contain at least one email address');
                }

                $isValidCallable = Validation::createIsValidCallable(new EmailConstraint());
                foreach ($recipients as $emailAddress) {
                    if (!$isValidCallable($emailAddress)) {
                        throw new InvalidOptionsException(
                            sprintf('Parameter "recipients" contains invalid email address: "%s"', $emailAddress)
                        );
                    }
                }

                return true;
            });
    }
}
