<?php

namespace Oro\Bundle\EntityConfigBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for removing attribute from family.
 */
class AttributeRemovedFromFamilyTopic extends AbstractTopic
{
    public const NAME = 'oro_entity_config.attribute_was_removed_from_family';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Removes an attribute from family';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'attributeFamilyId',
                'attributeNames',
            ])
            ->setRequired([
                'attributeFamilyId',
                'attributeNames'
            ])
            ->addAllowedTypes('attributeFamilyId', ['string', 'int'])
            ->addAllowedTypes('attributeNames', 'string[]');
    }
}
