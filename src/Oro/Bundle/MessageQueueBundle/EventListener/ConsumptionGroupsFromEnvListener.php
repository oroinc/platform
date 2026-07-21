<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\EventListener;

use Oro\Bundle\MessageQueueBundle\Event\TransportConsumeMessagesCommandConsoleEvent;
use Oro\Component\MessageQueue\Consumption\Exception\ConsumptionGroupsJsonException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Expands a consumption group argument for MQ consumption command into --queue options
 * using the ORO_MQ_CONSUMPTION_GROUPS env var.
 */
class ConsumptionGroupsFromEnvListener
{
    public function __construct(private readonly ?string $consumptionGroups)
    {
    }

    public function onConsoleCommand(TransportConsumeMessagesCommandConsoleEvent $event): void
    {
        if (!$this->consumptionGroups) {
            return;
        }

        if ($event->getInput()->hasParameterOption('--queue')) {
            return;
        }

        $consumptionGroupName = $event->getInput()->getArgument('queue');
        if ($consumptionGroupName === null || $consumptionGroupName === '') {
            return;
        }

        try {
            $consumptionGroups = json_decode($this->consumptionGroups, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw ConsumptionGroupsJsonException::create($exception);
        }

        if (!array_key_exists($consumptionGroupName, $consumptionGroups)) {
            return;
        }

        $queueOption = [];
        foreach ($consumptionGroups[$consumptionGroupName] as $queueName => $queueSettings) {
            if ($queueSettings === []) {
                $queueOption[] = $queueName;
            } else {
                $queueSettingsKeyValueString = array_map(
                    fn ($k, $v) => $k . '=' . $v,
                    array_keys($queueSettings),
                    $queueSettings
                );
                $queueOption[] = 'name=' . $queueName . ',' . implode(',', $queueSettingsKeyValueString);
            }
        }

        $event->getInput()->setArgument('queue', null);
        $event->getInput()->setOption('queue', $queueOption);

        $groupQueues = implode(', ', array_keys($consumptionGroups[$consumptionGroupName]));

        $symfonyOutput = new SymfonyStyle($event->getInput(), $event->getOutput());
        $symfonyOutput->note(
            sprintf(
                'Argument "%s" is recognized as a consumption group defined in the ORO_MQ_CONSUMPTION_GROUPS' .
                ' environment variable. Consumption queues have been switched to: %s.',
                $consumptionGroupName,
                $groupQueues
            )
        );
    }
}
