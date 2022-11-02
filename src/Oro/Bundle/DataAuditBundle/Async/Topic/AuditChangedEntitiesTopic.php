<?php

namespace Oro\Bundle\DataAuditBundle\Async\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Create audit entries for regular entity properties.
 */
class AuditChangedEntitiesTopic extends AbstractAuditTopic
{
    public static function getName(): string
    {
        return 'oro.data_audit.entities_changed';
    }

    public static function getDescription(): string
    {
        return 'Create audit entries for regular entity properties.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setDefaults([
                'entities_inserted' => [],
                'entities_updated' => [],
                'entities_deleted' => [],
                'collections_updated' => [],
            ])
            ->addAllowedTypes('entities_inserted', 'array')
            ->addAllowedTypes('entities_updated', 'array')
            ->addAllowedTypes('entities_deleted', 'array')
            ->addAllowedTypes('collections_updated', 'array');
    }
}
