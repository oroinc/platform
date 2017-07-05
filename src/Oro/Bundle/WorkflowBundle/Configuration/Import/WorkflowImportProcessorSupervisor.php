<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;

class WorkflowImportProcessorSupervisor implements ConfigImportProcessorInterface
{
    /** @var WorkflowImportProcessor[]|ArrayCollection */
    private $imports;

    /** @var WorkflowImportProcessor[]|ArrayCollection */
    private $processed;

    /** @var ConfigImportProcessorInterface */
    private $parent;

    public function __construct()
    {
        $this->imports = new ArrayCollection();
        $this->processed = new ArrayCollection();
    }

    /**
     * @param WorkflowImportProcessor $processor
     * @return $this
     */
    public function addImportProcessor(WorkflowImportProcessor $processor)
    {
        $this->imports[] = $processor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $content, \SplFileInfo $contentSource): array
    {
        /** @var WorkflowImportProcessor $processor */
        $processor = $this->imports->first();

        if ($processor === false || $this->shouldSkip($processor)) {
            return $content;
        }

        $this->imports->removeElement($processor);

        $this->checkCircularReferences($contentSource, $processor);

        if (null !== $this->parent) {
            $processor->setParent($this->parent);
        }

        $content = $processor->process($content, $contentSource);

        $this->addProcessed($processor);

        return $content;
    }

    /**
     * @param WorkflowImportProcessor $processor
     * @return string
     */
    private function getProcessorKey(WorkflowImportProcessor $processor): string
    {
        return sprintf('%s->%s', $processor->getResource(), $processor->getTarget());
    }

    /**
     * @param WorkflowImportProcessor $processor
     */
    private function addProcessed(WorkflowImportProcessor $processor)
    {
        $this->processed[$this->getProcessorKey($processor)] = $processor;
    }

    /**
     * @param WorkflowImportProcessor $processor
     * @return bool
     */
    private function isProcessed(WorkflowImportProcessor $processor): bool
    {
        return isset($this->processed[$this->getProcessorKey($processor)]);
    }

    /** {@inheritdoc} */
    public function setParent(ConfigImportProcessorInterface $parentProcessor)
    {
        $this->parent = $parentProcessor;
    }

    /**
     * @return ArrayCollection|WorkflowImportProcessor[]
     */
    public function getInProgress()
    {
        $filter = function (WorkflowImportProcessor $importProcessor) {
            return $importProcessor->inProgress();
        };

        return $this->imports->filter($filter);
    }

    /**
     * @param \SplFileInfo $contentSource
     * @param WorkflowImportProcessor $current
     */
    private function checkCircularReferences(\SplFileInfo $contentSource, WorkflowImportProcessor $current)
    {
        foreach ($this->getInProgress() as $importProcessor) {
            if ($importProcessor->getTarget() !== $current->getResource()) {
                continue;
            }

            throw new \LogicException(
                sprintf(
                    'Recursion met. File `%s` tries to import workflow `%s`' .
                    ' for `%s` that currently imports it too in `%s`',
                    $contentSource->getRealPath(),
                    $current->getTarget(),
                    $importProcessor->getTarget(),
                    $importProcessor->getProgressFile()
                )
            );
        }
    }

    /**
     * @param WorkflowImportProcessor $current
     * @return bool
     */
    private function shouldSkip(WorkflowImportProcessor $current): bool
    {
        //same import was already processed (incorrect usage)
        if ($this->isProcessed($current)) {
            return true;
        }

        return false;
    }
}
