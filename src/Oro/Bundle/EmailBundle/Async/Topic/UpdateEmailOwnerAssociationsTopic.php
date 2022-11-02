<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Update multiple emails for email owner.
 */
class UpdateEmailOwnerAssociationsTopic extends AbstractTopic
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
}
