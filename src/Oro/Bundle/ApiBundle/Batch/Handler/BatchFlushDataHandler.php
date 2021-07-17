<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * The flush data handler for ORM entities.
 */
class BatchFlushDataHandler implements BatchFlushDataHandlerInterface
{
    /** @var string */
    private $entityClass;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntityManagerInterface|null */
    private $entityManager;

    public function __construct(string $entityClass, DoctrineHelper $doctrineHelper)
    {
        $this->entityClass = $entityClass;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function startFlushData(array $items): void
    {
        if (null !== $this->entityManager) {
            throw new \LogicException('The flush data already started.');
        }

        $this->entityManager = $this->doctrineHelper->getEntityManagerForClass($this->entityClass);
    }

    /**
     * {@inheritDoc}
     */
    public function flushData(array $items): void
    {
        if (null === $this->entityManager) {
            throw new \LogicException('The flush data is not started.');
        }

        foreach ($items as $item) {
            $itemContext = $item->getContext();
            if ($itemContext->getTargetAction() === ApiAction::CREATE && !$itemContext->hasErrors()) {
                $itemTargetContext = $itemContext->getTargetContext();
                if (null !== $itemTargetContext) {
                    $entity = $itemTargetContext->getResult();
                    if (null !== $entity) {
                        $this->entityManager->persist($entity);
                    }
                }
            }
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->entityManager->getConnection()->rollBack();

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function finishFlushData(array $items): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        if (null !== $this->entityManager) {
            $this->entityManager->clear();
            $this->entityManager = null;
        }
    }
}
