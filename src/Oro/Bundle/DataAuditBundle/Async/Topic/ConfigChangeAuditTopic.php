<?php

namespace Oro\Bundle\DataAuditBundle\Async\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Creates a Data Audit entry for a system configuration change (any scope level).
 */
class ConfigChangeAuditTopic extends AbstractAuditTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.data_audit.config_changed';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Creates a Data Audit entry for a system configuration change.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired(['object_class', 'object_id', 'object_name', 'action', 'changes'])
            ->addAllowedTypes('object_class', 'string')
            ->addAllowedTypes('object_id', 'string')
            ->addAllowedTypes('object_name', 'string')
            ->addAllowedTypes('action', 'string')
            ->addAllowedTypes('changes', 'array');
    }
}
