<?php

namespace Oro\Bundle\BatchBundle\Step;

use Oro\Bundle\BatchBundle\Exception\InvalidItemException;

/**
 * Executor for the import/export step. Write batch size processed by a writer
 */
class CumulativeStepExecutor extends StepExecutor
{
    /**
     * {@inheritdoc}
     */
    public function execute(StepExecutionWarningHandlerInterface $warningHandler = null): void
    {
        try {
            $stopExecution = false;
            while (!$stopExecution) {
                try {
                    $readItem = $this->reader->read();
                    if (null === $readItem) {
                        $stopExecution = true;
                        continue;
                    }
                } catch (InvalidItemException $e) {
                    $this->handleStepExecutionWarning($this->reader, $e, $warningHandler);

                    continue;
                }

                $processedItem = $this->process($readItem, $warningHandler);
                $processedItems = $processedItem !== null ? [$processedItem] : [];

                /**
                 * Call a writer with empty data to allow UoW management during each iteration even with empty data.
                 * @see \Oro\Bundle\ImportExportBundle\Writer\CumulativeWriter::shouldFlush
                 */
                $this->write($processedItems, $warningHandler);
            }
        } finally {
            $this->ensureResourcesReleased($warningHandler);
        }
    }
}
