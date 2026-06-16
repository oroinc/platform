<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption;

/**
 * Parses a single --queue option value into its constituent parts: queue name, processor service ID,
 * and any queue settings.
 *
 * Each --queue option value may be either:
 *   - A plain string (just the queue name), e.g. oro.default
 *   - A key=value comma-separated string, e.g.
 *     name=oro.index,processor=oro_search.async.index_entity_processor,weight=10
 */
class QueueOptionValueParser
{
    /**
     * @return array{name: string, queueSettings: array{processor: string, ...array<string, string>}}
     */
    public function parse(string $value): array
    {
        $rawPairs = explode(',', $value);

        $useKeyValueMode = true;
        $hasNameKey = false;

        foreach ($rawPairs as $pair) {
            if (!str_contains($pair, '=')) {
                $useKeyValueMode = false;
                break;
            }

            $parts = explode('=', $pair, 2);
            if (trim($parts[0]) === 'name') {
                $hasNameKey = true;
            }
        }

        if (!$useKeyValueMode || !$hasNameKey) {
            return ['name' => trim($value), 'queueSettings' => [QueueConsumer::PROCESSOR => '']];
        }

        $associative = [];
        foreach ($rawPairs as $pair) {
            $parts = explode('=', $pair, 2);
            $associative[trim($parts[0])] = trim($parts[1] ?? '');
        }

        $name = trim($associative['name'] ?? '');
        $processor = trim($associative[QueueConsumer::PROCESSOR] ?? '');

        $queueSettings = $associative;
        unset($queueSettings['name'], $queueSettings[QueueConsumer::PROCESSOR]);

        return ['name' => $name, 'queueSettings' => [QueueConsumer::PROCESSOR => $processor, ...$queueSettings]];
    }
}
