<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is sent every time an import is completed.
 */
class FinishImportEvent extends Event
{
    public function __construct(
        private int $jobId,
        private string $processorAlias,
        private string $type,
        private array $options
    ) {
    }

    public function getJobId(): int
    {
        return $this->jobId;
    }

    public function getProcessorAlias(): string
    {
        return $this->processorAlias;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
