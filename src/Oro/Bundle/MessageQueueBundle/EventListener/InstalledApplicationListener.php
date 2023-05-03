<?php

namespace Oro\Bundle\MessageQueueBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\InstallerBundle\InstallerEvent;

/**
 * Processes all unique jobs from the queue by consumer after the oro:install or oro:platform:update command
 */
class InstalledApplicationListener
{
    public function __construct(
        private ?string $env,
        private ManagerRegistry $doctrine
    ) {
    }

    public function onFinishApplicationEvent(InstallerEvent $event): void
    {
        if (!$this->isTestEnvironment()) {
            return;
        }

        $event->getOutput()->writeln('<info>Processing consumer messages.</info>');

        try {
            $event->getCommandExecutor()->runCommand('oro:message-queue:consume', [
                '--stop-when-unique-jobs-processed' => true,
                '--process-isolation' => true,
                '--time-limit' => '+2 minutes'
            ]);

            // clean up jobs
            $connection = $this->doctrine->getConnection('message_queue');
            $connection->executeQuery('DELETE FROM oro_message_queue');
            $connection->executeQuery('DELETE FROM oro_message_queue_job');
            $connection->executeQuery('DELETE FROM oro_message_queue_job_unique');
        } catch (\ErrorException $exception) {
            throw new \Exception(
                'Something might went wrong with processors.'
                . ' You can check message_queue_job_unique table, if there are something then you can find job'
                . ' that was not processed properly.',
                previous: $exception
            );
        }
    }

    private function isTestEnvironment(): bool
    {
        return $this->env === 'test';
    }
}
