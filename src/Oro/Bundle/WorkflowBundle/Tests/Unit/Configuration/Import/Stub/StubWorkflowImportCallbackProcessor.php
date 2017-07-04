<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import\Stub;

use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowImportProcessor;

class StubWorkflowImportCallbackProcessor extends WorkflowImportProcessor
{
    /** @var callable */
    private $processCb;

    /** @param callable $processCb */
    public function __construct(callable $processCb)
    {
        $this->processCb = $processCb;
    }

    /** {@inheritdoc} */
    public function process(array $content, \SplFileInfo $contentSource): array
    {
        $this->inProgress = $contentSource;
        if ($this->parent) {
            $content = $this->parent->process($content, $contentSource);
        }

        $result = call_user_func($this->processCb, $content, $contentSource);
        $this->inProgress = null;

        return $result;
    }
}
