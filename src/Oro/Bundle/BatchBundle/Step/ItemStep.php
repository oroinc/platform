<?php

namespace Oro\Bundle\BatchBundle\Step;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Oro\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;

/**
 * Basic step implementation.
 */
class ItemStep extends AbstractStep implements StepExecutionWarningHandlerInterface
{
    protected ?int $batchSize = null;

    protected ?StepExecution $stepExecution = null;

    protected ?ItemReaderInterface $reader = null;

    protected ?ItemWriterInterface $writer = null;

    protected ?ItemProcessorInterface $processor = null;

    public function getBatchSize(): ?int
    {
        return $this->batchSize;
    }

    public function setBatchSize(?int $batchSize): self
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    public function getReader(): ?ItemReaderInterface
    {
        return $this->reader;
    }

    public function setReader(ItemReaderInterface $reader): void
    {
        $this->reader = $reader;
    }

    public function setWriter(ItemWriterInterface $writer): void
    {
        $this->writer = $writer;
    }

    public function getWriter(): ?ItemWriterInterface
    {
        return $this->writer;
    }

    public function setProcessor(ItemProcessorInterface $processor): void
    {
        $this->processor = $processor;
    }

    public function getProcessor(): ?ItemProcessorInterface
    {
        return $this->processor;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(): array
    {
        $stepElements = [
            $this->reader,
            $this->writer,
            $this->processor,
        ];
        $configuration = [];

        foreach ($stepElements as $stepElement) {
            if ($stepElement instanceof AbstractConfigurableStepElement) {
                foreach ($stepElement->getConfiguration() as $key => $value) {
                    if (!isset($configuration[$key]) || $value) {
                        $configuration[$key] = $value;
                    }
                }
            }
        }

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $config): void
    {
        $stepElements = [
            $this->reader,
            $this->writer,
            $this->processor,
        ];

        foreach ($stepElements as $stepElement) {
            if ($stepElement instanceof AbstractConfigurableStepElement) {
                $stepElement->setConfiguration($config);
            }
        }
    }

    public function getConfigurableStepElements(): array
    {
        return [
            'reader' => $this->getReader(),
            'processor' => $this->getProcessor(),
            'writer' => $this->getWriter(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(StepExecution $stepExecution)
    {
        $this->initializeStepElements($stepExecution);

        $stepExecutor = $this->createStepExecutor();
        $stepExecutor
            ->setReader($this->reader)
            ->setProcessor($this->processor)
            ->setWriter($this->writer);
        if (null !== $this->batchSize) {
            $stepExecutor->setBatchSize($this->batchSize);
        }

        $stepExecutor->execute($this);
        $this->flushStepElements();

        $this->restoreStepElements();
    }

    protected function createStepExecutor(): StepExecutor
    {
        return new StepExecutor();
    }

    protected function initializeStepElements(StepExecution $stepExecution): void
    {
        $this->stepExecution = $stepExecution;
        foreach ($this->getConfigurableStepElements() as $element) {
            if ($element instanceof StepExecutionAwareInterface) {
                $element->setStepExecution($stepExecution);
            }
            if (method_exists($element, 'initialize')) {
                $element->initialize();
            }
        }
    }

    /**
     * Restores step elements to a state that was before a job execution.
     */
    protected function restoreStepElements(): void
    {
        foreach ($this->getConfigurableStepElements() as $element) {
            if ($element instanceof StepExecutionRestoreInterface) {
                $element->restoreStepExecution();
            }
        }
    }

    public function flushStepElements(): void
    {
        foreach ($this->getConfigurableStepElements() as $element) {
            if (method_exists($element, 'flush')) {
                $element->flush();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleWarning($element, $name, $reason, array $reasonParameters, $item): void
    {
        $this->stepExecution->addWarning($name, $reason, $reasonParameters, $item);
        $this->dispatchInvalidItemEvent(get_class($element), $reason, $reasonParameters, $item);
    }
}
