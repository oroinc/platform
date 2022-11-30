<?php

namespace Oro\Bundle\AttachmentBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to remove image files related to removed attachment related entities
 */
class AttachmentRemoveImageTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro_attachment.remove_image';
    }

    public static function getDescription(): string
    {
        return 'Removes image files related to removed attachment related entities.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }
}
