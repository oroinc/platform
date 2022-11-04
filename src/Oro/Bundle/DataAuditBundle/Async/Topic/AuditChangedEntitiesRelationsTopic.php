<?php

namespace Oro\Bundle\DataAuditBundle\Async\Topic;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Create audit entries for entity relations.
 */
class AuditChangedEntitiesRelationsTopic extends AbstractAuditTopic
{
    public static function getName(): string
    {
        return 'oro.data_audit.entities_relations_changed';
    }

    public static function getDescription(): string
    {
        return 'Create audit entries for entity relations';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired([
                'collections_updated',
            ])
            ->setDefined([
                'entities_inserted',
                'entities_updated',
                'entities_deleted',
            ])
            ->setDefaults([
                'entities_inserted' => [],
                'entities_updated' => [],
                'entities_deleted' => [],
            ])
            ->addAllowedTypes('collections_updated', 'array')
            ->addAllowedTypes('entities_inserted', 'array')
            ->addAllowedTypes('entities_updated', 'array')
            ->addAllowedTypes('entities_deleted', 'array')
            ->addAllowedValues('collections_updated', static function ($value) {
                if (!count($value)) {
                    throw new InvalidOptionsException('The "collections_updated" was expected to be not empty.');
                }

                return true;
            });
    }
}
