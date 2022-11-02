<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Update visibilities for specific emails for organization.
 */
class UpdateEmailVisibilitiesForOrganizationChunkTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.email.update_email_visibilities_for_organization_chunk';
    }

    public static function getDescription(): string
    {
        return 'Update visibilities for specific emails for organization';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['jobId', 'organizationId', 'firstEmailId'])
            ->setDefault('lastEmailId', null)
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('organizationId', 'int')
            ->addAllowedTypes('firstEmailId', 'int')
            ->addAllowedTypes('lastEmailId', ['int', 'null']);
    }
}
