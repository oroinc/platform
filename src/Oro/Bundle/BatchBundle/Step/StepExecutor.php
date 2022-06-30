<?php

namespace Oro\Bundle\BatchBundle\Step;

use Oro\Bundle\BatchBundle\Exception\InvalidItemException;
use Oro\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Oro\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;

/**
 * Executor for the import/export step
 */
class StepExecutor
{
    protected int $batchSize = 100;

    protected ?ItemReaderInterface $reader = null;

    protected ?ItemWriterInterface $writer = null;

    protected ?ItemProcessorInterface $processor = null;

    public function setBatchSize(int $batchSize): self
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    public function getBatchSize(): int
    {
        return (int)$this->batchSize;
    }

    public function setReader(ItemReaderInterface $reader): self
    {
        $this->reader = $reader;

        return $this;
    }

    public function getReader(): ?ItemReaderInterface
    {
        return $this->reader;
    }

    public function setWriter(ItemWriterInterface $writer): self
    {
        $this->writer = $writer;

        return $this;
    }

    public function getWriter(): ?ItemWriterInterface
    {
        return $this->writer;
    }

    public function setProcessor(ItemProcessorInterface $processor): self
    {
        $this->processor = $processor;

        return $this;
    }

    public function getProcessor(): ?ItemProcessorInterface
    {
        return $this->processor;
    }

    /**
     * Executes a step
     *
     * @throws \InvalidItemException If any critical error occurs
     */
    public function execute(StepExecutionWarningHandlerInterface $warningHandler = null): void
    {
        $itemsToWrite = [];
        $writeCount = 0;

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
                if (null !== $processedItem) {
                    $itemsToWrite[] = $processedItem;
                    $writeCount++;
                    if (0 === $writeCount % $this->batchSize) {
                        $this->write($itemsToWrite, $warningHandler);
                        $itemsToWrite = [];
                    }
                }
            }

            if (count($itemsToWrite) > 0) {
                $this->write($itemsToWrite, $warningHandler);
            }

            $this->ensureResourcesReleased($warningHandler);
        } catch (\Exception $error) {
            $this->ensureResourcesReleased($warningHandler);
            throw $error;
        }
    }

    /**
     * @param mixed|null $readItem
     * @param StepExecutionWarningHandlerInterface|null $warningHandler
     *
     * @return mixed|null processed item
     */
    protected function process($readItem, StepExecutionWarningHandlerInterface $warningHandler = null)
    {
        try {
            return $this->processor->process($readItem);
        } catch (InvalidItemException $e) {
            $this->handleStepExecutionWarning($this->processor, $e, $warningHandler);

            return null;
        }
    }

    /**
     * @param array $processedItems
     * @param StepExecutionWarningHandlerInterface|null $warningHandler
     */
    protected function write($processedItems, StepExecutionWarningHandlerInterface $warningHandler = null): void
    {
        try {
            $this->writer->write($processedItems);
        } catch (InvalidItemException $e) {
            $this->handleStepExecutionWarning($this->writer, $e, $warningHandler);
        }
    }

    /**
     * Makes sure that all step elements are properly closed
     */
    protected function ensureResourcesReleased(StepExecutionWarningHandlerInterface $warningHandler = null): void
    {
        $this->ensureElementClosed($this->reader, $warningHandler);
        $this->ensureElementClosed($this->processor, $warningHandler);
        $this->ensureElementClosed($this->writer, $warningHandler);
    }

    /**
     * Makes sure that the given step element is properly closed
     */
    protected function ensureElementClosed(
        object $element,
        StepExecutionWarningHandlerInterface $warningHandler = null
    ): void {
        try {
            if ($element instanceof ClosableInterface) {
                $element->close();
            }
        } catch (\Exception $e) {
            $this->handleStepExecutionWarning($element, $e, $warningHandler);
        }
    }

    /**
     * Handle step execution warning
     */
    protected function handleStepExecutionWarning(
        object $element,
        \Exception $e,
        StepExecutionWarningHandlerInterface $warningHandler = null
    ): void {
        if (null !== $warningHandler) {
            $warningName = $element instanceof AbstractConfigurableStepElement
                ? $element->getName()
                : get_class($element);

            $item = [];
            $reasonParameters = [];
            if ($e instanceof InvalidItemException) {
                $item = $e->getItem();
                $reasonParameters = $e->getMessageParameters();
            }

            $warningHandler->handleWarning($element, $warningName, $e->getMessage(), $reasonParameters, $item);
        }
    }
}
