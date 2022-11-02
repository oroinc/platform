<?php

namespace Oro\Bundle\DataAuditBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Abstract base audit topic class.
 */
abstract class AbstractAuditTopic extends AbstractTopic
{
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'timestamp',
                'transaction_id',
                'user_id',
                'user_class',
                'organization_id',
                'impersonation_id',
                'owner_description',
            ])
            ->setRequired([
                'timestamp',
                'transaction_id',
            ])
            ->setDefaults([
                'user_id' => null,
                'user_class' => null,
                'organization_id' => null,
                'impersonation_id' => null,
                'owner_description' => null,
            ])
            ->addAllowedTypes('timestamp', 'int')
            ->addAllowedTypes('transaction_id', ['string', 'int'])
            ->addAllowedTypes('user_id', ['string', 'int', 'null'])
            ->addAllowedTypes('user_class', ['string', 'null'])
            ->addAllowedTypes('organization_id', ['string', 'int', 'null'])
            ->addAllowedTypes('impersonation_id', ['string', 'int', 'null'])
            ->addAllowedTypes('owner_description', ['string', 'null']);
    }
}
