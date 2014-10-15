<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Doctrine\ORM\EntityManager;

use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

class EntityWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    const SKIP_CLEAR = 'writer_skip_clear';

    /** @var EntityManager */
    protected $entityManager;

    /** @var EntityDetachFixer */
    protected $detachFixer;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var ContextRegistry */
    protected $contextRegistry;

    public function __construct(
        EntityManager $entityManager,
        EntityDetachFixer $detachFixer,
        ContextRegistry $contextRegistry
    ) {
        $this->entityManager   = $entityManager;
        $this->detachFixer     = $detachFixer;
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            $this->entityManager->persist($item);
            $this->detachFixer->fixEntityAssociationFields($item, 1);
        }
        $this->entityManager->flush();

        $configuration = $this->contextRegistry
            ->getByStepExecution($this->stepExecution)
            ->getConfiguration();

        if (empty($configuration[self::SKIP_CLEAR])) {
            $this->entityManager->clear();
        }
    }

    /**
     * @param StepExecution $stepExecution
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }
}
