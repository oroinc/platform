<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Update visibilities for emails for organization.
 */
class UpdateEmailVisibilitiesForOrganizationTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.email.update_email_visibilities_for_organization';
    }

    public static function getDescription(): string
    {
        return 'Update visibilities for emails for organization';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['organizationId'])
            ->addAllowedTypes('organizationId', 'int');
    }
}
