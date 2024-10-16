<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Update visibilities for emails for organization.
 */
class UpdateEmailVisibilitiesForOrganizationTopic extends AbstractTopic implements JobAwareTopicInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.email.update_email_visibilities_for_organization';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Update visibilities for emails for organization';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['organizationId'])
            ->addAllowedTypes('organizationId', 'int');
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        $organizationId = $messageBody['organizationId'];

        return sprintf('oro:email:update-visibilities:emails:%d', $organizationId);
    }
}
