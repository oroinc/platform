<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Add association to multiple emails.
 */
class AddEmailAssociationsTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.email.add_association_to_emails';
    }

    public static function getDescription(): string
    {
        return 'Add association to multiple emails';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'emailIds',
                'targetClass',
                'targetId',
            ])
            ->addAllowedTypes('emailIds', ['string[]', 'int[]'])
            ->addAllowedTypes('targetId', ['string', 'int'])
            ->addAllowedTypes('targetClass', 'string');
    }

    public function createJobName($messageBody): string
    {
        asort($messageBody['emailIds']);

        return sprintf(
            '%s:%s:%s:%s',
            'oro.email.add_association_to_emails',
            $messageBody['targetClass'],
            $messageBody['targetId'],
            md5(implode(',', $messageBody['emailIds']))
        );
    }
}
