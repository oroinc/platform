<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Add association to single email.
 */
class AddEmailAssociationTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.email.add_association_to_email';
    }

    public static function getDescription(): string
    {
        return 'Add association to single email';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'jobId',
                'emailId',
                'targetClass',
                'targetId',
            ])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('emailId', ['string', 'int'])
            ->addAllowedTypes('targetId', ['string', 'int'])
            ->addAllowedTypes('targetClass', 'string');
    }
}
