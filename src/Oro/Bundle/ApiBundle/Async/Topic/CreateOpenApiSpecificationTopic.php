<?php

namespace Oro\Bundle\ApiBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to create or renew OpenAPI specification.
 */
class CreateOpenApiSpecificationTopic extends AbstractTopic
{
    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return 'oro.api.create_open_api_specification';
    }

    /**
     * {@inheritDoc}
     */
    public static function getDescription(): string
    {
        return 'Creates OpenAPI specification.';
    }

    /**
     * {@inheritDoc}
     */
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('entityId')
            ->setAllowedTypes('entityId', 'int');

        $resolver
            ->setDefault('renew', false)
            ->setAllowedTypes('renew', 'bool');
    }
}
