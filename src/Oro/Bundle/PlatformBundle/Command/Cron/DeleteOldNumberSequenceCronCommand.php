<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Command\Cron;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\PlatformBundle\Async\Topic\DeleteOldNumberSequenceTopic;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Entity\Repository\NumberSequenceRepository;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cron command to schedule deletion of old number sequences.
 */
#[AsCommand(
    name: 'oro:cron:platform:delete-old-number-sequences',
    description: 'Delete old number sequences that are no longer needed'
)]
class DeleteOldNumberSequenceCronCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly MessageProducerInterface $messageProducer,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 0 * * *';
    }

    #[\Override]
    public function isActive(): bool
    {
        return $this->getRepository()->hasSequences();
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Starting deletion of number sequences...');

        $uniqueSequenceTypes = $this->getRepository()->findUniqueSequenceTypes();
        $totalScheduled = $this->scheduleSequencesForDeletion($uniqueSequenceTypes, $io);

        $io->success(sprintf('Deletion has been queued for %d sequence types.', $totalScheduled));

        return Command::SUCCESS;
    }

    private function getRepository(): NumberSequenceRepository
    {
        return $this->doctrine->getRepository(NumberSequence::class);
    }

    /**
     * @param array{sequenceType: string, discriminatorType: string} $sequenceType
     */
    private function sendDeletionMessage(array $sequenceType): void
    {
        $this->messageProducer->send(
            DeleteOldNumberSequenceTopic::getName(),
            [
                'sequenceType' => $sequenceType['sequenceType'],
                'discriminatorType' => $sequenceType['discriminatorType']
            ]
        );
    }

    /**
     * @param array{sequenceType: string, discriminatorType: string} $sequenceType
     */
    private function logScheduling(SymfonyStyle $io, array $sequenceType): void
    {
        $io->text(
            sprintf(
                'Scheduling deletion for sequence type "%s" with discriminator type "%s"',
                $sequenceType['sequenceType'],
                $sequenceType['discriminatorType']
            )
        );
    }

    /**
     * @param array<array{sequenceType: string, discriminatorType: string}> $sequenceTypes
     */
    private function scheduleSequencesForDeletion(array $sequenceTypes, SymfonyStyle $io): int
    {
        $scheduled = 0;

        foreach ($sequenceTypes as $sequenceType) {
            $this->logScheduling($io, $sequenceType);
            $this->sendDeletionMessage($sequenceType);
            $scheduled++;
        }

        return $scheduled;
    }
}
