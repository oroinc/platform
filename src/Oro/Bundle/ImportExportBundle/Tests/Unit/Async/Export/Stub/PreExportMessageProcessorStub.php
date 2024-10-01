<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export\Stub;

use Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessorAbstract;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class PreExportMessageProcessorStub extends PreExportMessageProcessorAbstract
{
    /** @var array */
    private $messageBody = [];

    /** @var string */
    private $jobUniqueName;

    /** @var array */
    private $exportingEntityIds = [];

    public function setExportingEntityIds(array $exportingEntityIds): void
    {
        $this->exportingEntityIds = $exportingEntityIds;
    }

    public function setJobUniqueName(string $jobUniqueName): void
    {
        $this->jobUniqueName = $jobUniqueName;
    }

    public function setMessageBody(array $messageBody): void
    {
        $this->messageBody = $messageBody;
    }

    protected function getJobUniqueName(array $body): string
    {
        return $this->jobUniqueName;
    }

    #[\Override]
    protected function getExportingEntityIds(array $body): array
    {
        return $this->exportingEntityIds;
    }

    #[\Override]
    protected function getDelayedJobCallback(array $body, array $ids = [])
    {
        $closure = function () {
        };

        return $closure;
    }

    #[\Override]
    protected function getMessageBody(MessageInterface $message): array
    {
        return $this->messageBody;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [];
    }
}
