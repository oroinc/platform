<?php

namespace Oro\Bundle\ApiBundle\Monolog\Handler;

use Monolog\Handler\AbstractHandler;
use Monolog\LogRecord;
use Oro\Bundle\ApiBundle\Collector\ApiDocWarningsCollector;

/**
 * Monolog handler that silently collects unique warning messages for API documentation.
 *
 * This handler intercepts log messages, deduplicates them, and forwards unique warnings
 * to the collector without propagating them further in the logging chain.
 * It prevents duplicate warnings from being collected multiple times during documentation generation.
 */
class ApiDocCollectingHandler extends AbstractHandler
{
    /** @var array<string, true> Hash map of seen messages for deduplication */
    private array $seen = [];

    public function __construct(private ApiDocWarningsCollector $collector)
    {
        parent::__construct();
    }

    #[\Override]
    public function handle(LogRecord $record): bool
    {
        $message = $record['message'];

        if ($this->isMessageSeen($message)) {
            return false;
        }

        $this->markAsSeen($message);
        $this->collector->addWarning($message);

        return false; // Don't propagate to other handlers
    }

    private function isMessageSeen(string $message): bool
    {
        return isset($this->seen[$this->getMessageHash($message)]);
    }

    private function markAsSeen(string $message): void
    {
        $this->seen[$this->getMessageHash($message)] = true;
    }

    private function getMessageHash(string $message): string
    {
        return md5($message);
    }
}
