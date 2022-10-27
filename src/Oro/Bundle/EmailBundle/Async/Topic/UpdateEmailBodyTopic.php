<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Updates email body.
 */
class UpdateEmailBodyTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro_email.migrate_email_body';
    }

    public static function getDescription(): string
    {
        return 'Updates email body with plain text representation.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('pageNumber')
            ->addAllowedTypes('pageNumber', 'int');
    }
}
