<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Provides functionality for retrieving a channel entity based on a webhook ID.
 */
trait WebhookAwareChannelRepositoryTrait
{
    public function getChannelByWebhookId(string $webhookId): Channel
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->from(Channel::class, 'c')
            ->select('c')
            ->join($this->getEntityName(), 't', Join::WITH, 'c.transport = t')
            ->where('IDENTITY(t.webhook) = :webhookId')
            ->setParameter('webhookId', $webhookId);

        return $qb->getQuery()->getSingleResult();
    }
}
