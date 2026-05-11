<?php

namespace Oro\Bundle\IntegrationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;

/**
 * Doctrine repository for WebhookProducerSettings entity.
 */
class WebhookProducerSettingsRepository extends EntityRepository
{
    /**
     * Check if there are active notifications for the given channel and event
     */
    public function hasActiveWebhooks(string $topic): bool
    {
        $record = $this->createQueryBuilder('rn')
            ->select('rn.id')
            ->andWhere('rn.topic = :topic')
            ->andWhere('rn.enabled = :enabled')
            ->setParameter('topic', $topic)
            ->setParameter('enabled', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $record !== null;
    }

    /**
     * Get active webhooks for the given channel and event
     *
     * @return WebhookProducerSettings[]
     */
    public function getActiveWebhooks(string $topic): array
    {
        return $this->createQueryBuilder('rn')
            ->where('rn.topic = :topic')
            ->andWhere('rn.enabled = :enabled')
            ->setParameter('topic', $topic)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult();
    }
}
