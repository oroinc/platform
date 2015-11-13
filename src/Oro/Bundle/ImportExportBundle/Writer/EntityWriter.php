<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

class EntityWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    const SKIP_CLEAR = 'writer_skip_clear';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var EntityDetachFixer */
    protected $detachFixer;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var ContextRegistry */
    protected $contextRegistry;

    public function __construct(
        ManagerRegistry $registry,
        EntityDetachFixer $detachFixer,
        ContextRegistry $contextRegistry
    ) {
        $this->registry   = $registry;
        $this->detachFixer     = $detachFixer;
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $entityManager = array_key_exists(0, $items)
            ? $this->registry->getManagerForClass(ClassUtils::getRealClass($items[0]))
            : $this->registry->getManager();
        foreach ($items as $item) {
            $entityManager->persist($item);
            $this->detachFixer->fixEntityAssociationFields($item, 1);
        }
        $entityManager->flush();

        $configuration = $this->contextRegistry
            ->getByStepExecution($this->stepExecution)
            ->getConfiguration();

        if (empty($configuration[self::SKIP_CLEAR])) {
            $entityManager->clear();
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
