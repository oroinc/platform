<?php

namespace Oro\Bundle\BatchBundle\Step;

use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;

class StepExecutor
{
    /**
     * @var int
     */
    protected $batchSize = 100;

    /**
     * @var ItemReaderInterface
     */
    protected $reader = null;

    /**
     * @var ItemWriterInterface
     */
    protected $writer = null;

    /**
     * @var ItemProcessorInterface
     */
    protected $processor = null;

    /**
     * Set the batch size
     *
     * @param integer $batchSize
     *
     * @return StepExecutor
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    /**
     * Get the batch size
     *
     * @return integer
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * Set reader
     *
     * @param ItemReaderInterface $reader
     *
     * @return StepExecutor
     */
    public function setReader(ItemReaderInterface $reader)
    {
        $this->reader = $reader;

        return $this;
    }

    /**
     * Get reader
     *
     * @return ItemReaderInterface|null
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * Set writer
     *
     * @param ItemWriterInterface $writer
     *
     * @return StepExecutor
     */
    public function setWriter(ItemWriterInterface $writer)
    {
        $this->writer = $writer;

        return $this;
    }

    /**
     * Get writer
     *
     * @return ItemWriterInterface|null
     */
    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * Set processor
     *
     * @param ItemProcessorInterface $processor
     *
     * @return StepExecutor
     */
    public function setProcessor(ItemProcessorInterface $processor)
    {
        $this->processor = $processor;

        return $this;
    }

    /**
     * Get processor
     *
     * @return ItemProcessorInterface|null
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Executes a step
     *
     * @param StepExecutionWarningHandlerInterface|null $warningHandler
     *
     * @throws \Exception If any critical error occurs
     */
    public function execute(StepExecutionWarningHandlerInterface $warningHandler = null)
    {
        $itemsToWrite = array();
        $writeCount   = 0;

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
                        $itemsToWrite = array();
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
     * @param mixed                                     $readItem
     * @param StepExecutionWarningHandlerInterface|null $warningHandler
     *
     * @return mixed processed item
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
     * @param array                                     $processedItems
     * @param StepExecutionWarningHandlerInterface|null $warningHandler
     *
     * @return null
     */
    protected function write($processedItems, StepExecutionWarningHandlerInterface $warningHandler = null)
    {
        try {
            $this->writer->write($processedItems);
        } catch (InvalidItemException $e) {
            $this->handleStepExecutionWarning($this->writer, $e, $warningHandler);
        }
    }

    /**
     * Makes sure that all step elements are properly closed
     *
     * @param StepExecutionWarningHandlerInterface|null $warningHandler
     */
    protected function ensureResourcesReleased(StepExecutionWarningHandlerInterface $warningHandler = null)
    {
        $this->ensureElementClosed($this->reader, $warningHandler);
        $this->ensureElementClosed($this->processor, $warningHandler);
        $this->ensureElementClosed($this->writer, $warningHandler);
    }

    /**
     * Makes sure that the given step element is properly closed
     *
     * @param                                           $element
     * @param StepExecutionWarningHandlerInterface|null $warningHandler
     */
    protected function ensureElementClosed(
        $element,
        StepExecutionWarningHandlerInterface $warningHandler = null
    ) {
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
     *
     * @param object                                    $element
     * @param \Exception                                $e
     * @param StepExecutionWarningHandlerInterface|null $warningHandler
     */
    protected function handleStepExecutionWarning(
        $element,
        \Exception $e,
        StepExecutionWarningHandlerInterface $warningHandler = null
    ) {
        if (null !== $warningHandler) {
            $warningName = $element instanceof AbstractConfigurableStepElement
                ? $element->getName()
                : get_class($element);
            $item = $e instanceof InvalidItemException
                ? $e->getItem()
                : null;

            $warningHandler->handleWarning($element, $warningName, $e->getMessage(), $e->getMessageParameters(), $item);
        }
    }
}
