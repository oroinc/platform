<?php

namespace Oro\Bundle\DataAuditBundle\Async\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Create a Data Audit entry built by a bundle for a change that is not a change of an auditable entity.
 *
 * @see \Oro\Bundle\DataAuditBundle\Service\AuditEntryRecorder
 */
class AuditEntryTopic extends AbstractAuditTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.data_audit.audit_entry';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Creates a Data Audit entry recorded by a bundle.';
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
