<?php

namespace Oro\Bundle\PlatformBundle\Monolog;

use Symfony\Bridge\Monolog\Processor\DebugProcessor;

/**
 * Decorates DebugProcessor to allow disabling of deprecations logging as there are too many of them.
 * This significantly improves dev environment performance.
 */
class DeprecationDebugProcessor extends DebugProcessor
{
    private bool $collectDeprecations;

    public function setCollectDeprecations(bool $collectDeprecations): void
    {
        $this->collectDeprecations = $collectDeprecations;
    }

    public function __invoke(array $record): array
    {
        if (!$this->collectDeprecations && $this->isDeprecationErrorLogRecord($record)) {
            // return a record without processing
            return $record;
        }

        return parent::__invoke($record);
    }

    private function isDeprecationErrorLogRecord(array $record): bool
    {
        if (!isset($record['context']['exception'])) {
            return false;
        }

        $exception = $record['context']['exception'];
        if ($exception instanceof \ErrorException &&
            \in_array($exception->getSeverity(), [\E_DEPRECATED, \E_USER_DEPRECATED], true)) {
            return true;
        }

        return false;
    }
}
