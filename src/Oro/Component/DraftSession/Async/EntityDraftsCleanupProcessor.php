<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Async;

use Oro\Component\DraftSession\Cleanup\EntityDraftsCleanupStrategyInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Processes deletion of outdated draft entities.
 */
class EntityDraftsCleanupProcessor implements MessageProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private int $batchSize = 100;

    public function __construct(
        private readonly EntityDraftsCleanupStrategyInterface $entityDraftsCleanupStrategy,
    ) {
        $this->logger = new NullLogger();
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageData = $message->getBody();
        $draftLifetimeDays = $messageData['draftLifetimeDays'];

        $this->logger->info(
            'Starting cleanup of outdated drafts',
            ['draftLifetimeDays' => $draftLifetimeDays]
        );

        $threshold = new \DateTime(
            sprintf('today -%d days', $draftLifetimeDays),
            new \DateTimeZone('UTC')
        );

        $totalRemoved = $this->entityDraftsCleanupStrategy->cleanupEntityDrafts($threshold, $this->batchSize);

        $this->logger->info(
            'Successfully completed cleanup of outdated drafts',
            [
                'totalRemoved' => $totalRemoved,
                'draftLifetimeDays' => $draftLifetimeDays
            ]
        );

        return self::ACK;
    }
}
