<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ParameterBag;

/**
 * The flush data handler for ORM entities.
 */
class BatchFlushDataHandler implements BatchFlushDataHandlerInterface
{
    private string $entityClass;
    private DoctrineHelper $doctrineHelper;
    private FlushDataHandlerInterface $flushDataHandler;
    private ?EntityManagerInterface $entityManager = null;

    public function __construct(
        string $entityClass,
        DoctrineHelper $doctrineHelper,
        FlushDataHandlerInterface $flushDataHandler
    ) {
        $this->entityClass = $entityClass;
        $this->doctrineHelper = $doctrineHelper;
        $this->flushDataHandler = $flushDataHandler;
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

        $itemTargetContexts = [];
        $staredData = null;
        foreach ($items as $item) {
            $itemContext = $item->getContext();
            if ($itemContext->hasErrors()) {
                continue;
            }
            $itemTargetContext = $itemContext->getTargetContext();
            if (null === $itemTargetContext) {
                continue;
            }
            $itemEntity = $itemTargetContext->getResult();
            if (!\is_object($itemEntity)) {
                continue;
            }
            if ($itemTargetContext instanceof FormContext) {
                $itemTargetContexts[] = $itemTargetContext;
                if (null === $staredData) {
                    $staredData = $itemTargetContext->getSharedData();
                }
            }
            if ($itemContext->getTargetAction() === ApiAction::CREATE) {
                $this->entityManager->persist($itemEntity);
            }
        }

        $this->flushDataHandler->flushData(
            $this->entityManager,
            new FlushDataHandlerContext($itemTargetContexts, $staredData ?? new ParameterBag())
        );
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
