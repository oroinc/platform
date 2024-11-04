<?php

namespace Oro\Bundle\ApiBundle\Batch\Async\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to finish the processing of API batch update request.
 */
class UpdateListFinishTopic extends AbstractUpdateListTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.api.update_list.finish';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Finishes the processing of API batch update request.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired('fileName')
            ->setAllowedTypes('fileName', 'string');
    }
}
