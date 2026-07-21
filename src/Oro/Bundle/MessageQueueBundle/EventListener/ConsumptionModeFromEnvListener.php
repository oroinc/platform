<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\EventListener;

use Oro\Bundle\MessageQueueBundle\Event\TransportConsumeMessagesCommandConsoleEvent;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Sets the --mode option for MQ consumption command based on the ORO_MQ_CONSUMPTION_MODE env var
 * if the option is not set explicitly.
 */
class ConsumptionModeFromEnvListener
{
    public function __construct(private readonly ?string $consumptionMode)
    {
    }

    public function onConsoleCommand(TransportConsumeMessagesCommandConsoleEvent $event): void
    {
        if (!$event->getCommand()->getDefinition()->hasOption('mode')) {
            return;
        }

        if (!$this->consumptionMode) {
            return;
        }

        if ($event->getInput()->hasParameterOption('--mode')) {
            return;
        }

        $event->getInput()->setOption('mode', $this->consumptionMode);

        $symfonyOutput = new SymfonyStyle($event->getInput(), $event->getOutput());
        $symfonyOutput->note(
            sprintf(
                'Consumption mode set to "%s" based on the ORO_MQ_CONSUMPTION_MODE environment variable.',
                $this->consumptionMode
            )
        );
    }
}
