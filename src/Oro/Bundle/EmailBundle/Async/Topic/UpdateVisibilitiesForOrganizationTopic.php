<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Update visibilities for emails and email addresses for organization.
 */
class UpdateVisibilitiesForOrganizationTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.email.update_visibilities_for_organization';
    }

    public static function getDescription(): string
    {
        return 'Update visibilities for emails and email addresses for organization';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['jobId', 'organizationId'])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('organizationId', 'int');
    }
}
