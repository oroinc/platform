<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Update multiple emails for email owner.
 */
class UpdateEmailOwnerAssociationsTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.email.update_email_owner_associations';
    }

    public static function getDescription(): string
    {
        return 'Update multiple emails for email owner';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'ownerClass',
                'ownerIds',
            ])
            ->addAllowedTypes('ownerIds', ['string[]', 'int[]'])
            ->addAllowedTypes('ownerClass', 'string');
    }

    public function createJobName($messageBody): string
    {
        asort($messageBody['ownerIds']);

        return sprintf(
            '%s:%s:%s',
            'oro.email.update_email_owner_associations',
            $messageBody['ownerClass'],
            md5(implode(',', $messageBody['ownerIds']))
        );
    }
}
