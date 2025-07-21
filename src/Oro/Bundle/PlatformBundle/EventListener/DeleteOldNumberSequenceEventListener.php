<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Event\DeleteOldNumberSequenceEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Listens to DeleteOldNumberSequenceEvent to delete old NumberSequence records, keeping the latest one.
 */
final class DeleteOldNumberSequenceEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param ManagerRegistry $doctrine Doctrine registry for database operations
     * @param string $sequenceType The type of sequence (e.g., 'invoice', 'order')
     * @param string $discriminatorType The subtype or context of the sequence (e.g., 'organization_periodic',
     * 'regular')
     */
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly string $sequenceType,
        private readonly string $discriminatorType,
    ) {
    }

    public function onDeleteOldNumberSequence(DeleteOldNumberSequenceEvent $event): void
    {
        if (!$this->shouldProcessEvent($event)) {
            return;
        }

        try {
            $sequences = $this->findSequencesToProcess();
            if (count($sequences) <= 1) {
                return;
            }

            $this->deleteOldSequences($sequences);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to delete old number sequences for sequenceType: {sequenceType},'
                . 'discriminatorType: {discriminatorType}',
                [
                    'sequenceType' => $this->sequenceType,
                    'discriminatorType' => $this->discriminatorType,
                    'exception' => $e->getMessage(),
                ]
            );
        }
    }

    private function shouldProcessEvent(DeleteOldNumberSequenceEvent $event): bool
    {
        return $event->getSequenceType() === $this->sequenceType
            && $event->getDiscriminatorType() === $this->discriminatorType;
    }

    /**
     * @return list<NumberSequence>
     */
    private function findSequencesToProcess(): array
    {
        return $this->doctrine
            ->getRepository(NumberSequence::class)
            ->findByTypeAndDiscriminatorOrdered($this->sequenceType, $this->discriminatorType);
    }

    /**
     * @param list<NumberSequence> $sequences
     */
    private function deleteOldSequences(array $sequences): void
    {
        $manager = $this->doctrine->getManagerForClass(NumberSequence::class);
        array_shift($sequences);

        foreach ($sequences as $sequence) {
            $manager->remove($sequence);
        }

        $manager->flush();
    }
}
