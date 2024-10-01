<?php

namespace Oro\Bundle\EntityExtendBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for actualize entity enum options.
 */
class ActualizeEntityEnumOptionsTopic extends AbstractTopic
{
    public const string ENUM_CODE = 'enumCode';
    public const string ENUM_OPTION_ID = 'id';

    #[\Override]
    public static function getName(): string
    {
        return 'oro.entity_extend.actualize_entity_enum_options';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Actualize entity enum options';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define(self::ENUM_CODE)
            ->required()
            ->allowedTypes('string');
        $resolver
            ->define(self::ENUM_OPTION_ID)
            ->required()
            ->allowedTypes('string');
    }
}
