<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Doctrine\Common\Util\ClassUtils;
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

    #[\Override]
    public function startFlushData(array $items): void
    {
        if (null !== $this->entityManager) {
            throw new \LogicException('The flush data already started.');
        }

        $this->entityManager = $this->doctrineHelper->getEntityManagerForClass($this->entityClass);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function flushData(array $items): void
    {
        if (null === $this->entityManager) {
            throw new \LogicException('The flush data is not started.');
        }

        $itemTargetContexts = [];
        $sharedData = null;
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
            $isNewEntity = $itemContext->getTargetAction() === ApiAction::CREATE;
            if ($itemTargetContext instanceof FormContext) {
                $isNewEntity = !$itemTargetContext->isExisting();
                $itemTargetContexts[] = $itemTargetContext;
                if (null === $sharedData) {
                    $sharedData = $itemTargetContext->getSharedData();
                }
            }
            if ($isNewEntity && $this->isManageableEntity($this->entityManager, $itemEntity)) {
                $this->entityManager->persist($itemEntity);
            }
        }

        $this->flushDataHandler->flushData(
            $this->entityManager,
            new FlushDataHandlerContext($itemTargetContexts, $sharedData ?? new ParameterBag(), true)
        );
    }

    #[\Override]
    public function finishFlushData(array $items): void
    {
    }

    #[\Override]
    public function clear(): void
    {
        if (null !== $this->entityManager) {
            $this->entityManager->clear();
            $this->entityManager = null;
        }
    }

    public function isManageableEntity(EntityManagerInterface $entityManager, object $entity): bool
    {
        return !$entityManager->getMetadataFactory()->isTransient(ClassUtils::getClass($entity));
    }
}
