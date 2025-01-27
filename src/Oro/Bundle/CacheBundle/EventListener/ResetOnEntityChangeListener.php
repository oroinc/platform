<?php

declare(strict_types=1);

namespace Oro\Bundle\CacheBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Component\DoctrineUtils\ORM\ChangedEntityGeneratorTrait;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Resets the specified service when a tracked entity is inserted/updated/deleted.
 */
class ResetOnEntityChangeListener
{
    use ChangedEntityGeneratorTrait;

    private bool $doReset = false;

    public function __construct(private ResetInterface $serviceToReset, private array $entityClasses)
    {
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        foreach ($this->getChangedEntities($event->getObjectManager()->getUnitOfWork()) as $entity) {
            if (in_array(ClassUtils::getClass($entity), $this->entityClasses, true)) {
                $this->doReset = true;
                break;
            }
        }
    }

    public function postFlush(): void
    {
        if ($this->doReset === false) {
            return;
        }

        $this->doReset = false;
        $this->serviceToReset->reset();
    }

    public function onClear(): void
    {
        $this->doReset = false;
    }
}
