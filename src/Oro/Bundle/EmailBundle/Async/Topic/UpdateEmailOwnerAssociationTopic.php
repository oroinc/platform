<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Update single email for email owner.
 */
class UpdateEmailOwnerAssociationTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.email.update_email_owner_association';
    }

    public static function getDescription(): string
    {
        return 'Update single email for email owner';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'jobId',
                'ownerClass',
                'ownerId',
            ])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('ownerId', ['string', 'int'])
            ->addAllowedTypes('ownerClass', 'string');
    }
}
