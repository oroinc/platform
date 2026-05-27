<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Isolator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Event\OroEntityListenerResolver;
use Oro\Bundle\EntityBundle\Event\OroEventManager;
use Oro\Component\DraftSession\Exception\DraftSessionLogicException;

/**
 * Isolates Doctrine event listeners during a draft session by disabling all listeners
 * except those explicitly whitelisted, preventing unintended side effects when
 * persisting or flushing draft entities.
 */
class DoctrineListenersIsolator
{
    /**
     * @param ManagerRegistry $doctrine
     * @param string $entityClass Entity class to retrieve an entity manager for.
     * @param list<string> $whitelistedListenerClasses A list of event listener classes that should not be disabled.
     */
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly string $entityClass,
        private array $whitelistedListenerClasses = []
    ) {
    }

    /**
     * Disables all Doctrine event listeners except the whitelisted ones.
     */
    public function disableListeners(): void
    {
        $classNameRegexp = $this->buildExceptClassesRegexp($this->whitelistedListenerClasses);

        $eventManager = $this->getEventManager();
        $eventManager->disableListeners($classNameRegexp);

        $entityListenerResolver = $this->getEntityListenerResolver();
        $entityListenerResolver->disableListeners($classNameRegexp);
    }

    /**
     * Re-enables all previously disabled Doctrine event listeners.
     */
    public function enableListeners(): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->clearDisabledListeners();

        $entityListenerResolver = $this->getEntityListenerResolver();
        $entityListenerResolver->clearDisabledListeners();
    }

    private function getEventManager(): OroEventManager
    {
        $eventManager = $this->getEntityManager()->getEventManager();
        if (!$eventManager instanceof OroEventManager) {
            throw new DraftSessionLogicException(
                sprintf(
                    'Event manager is expected to be an instance of %s',
                    OroEventManager::class
                )
            );
        }

        return $eventManager;
    }

    private function getEntityListenerResolver(): OroEntityListenerResolver
    {
        $entityListenerResolver = $this->getEntityManager()->getConfiguration()->getEntityListenerResolver();
        if (!$entityListenerResolver instanceof OroEntityListenerResolver) {
            throw new DraftSessionLogicException(
                sprintf(
                    'Entity listener resolver is expected to be an instance of %s',
                    OroEntityListenerResolver::class
                )
            );
        }

        return $entityListenerResolver;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $entityManager = $this->doctrine->getManagerForClass($this->entityClass);
        assert($entityManager instanceof EntityManagerInterface);

        return $entityManager;
    }

    /**
     * Builds a regexp that matches any listener class name NOT in the given list.
     *
     * @param list<string> $classNames A list of whitelisted event listener classes.
     *
     * @return string
     */
    private function buildExceptClassesRegexp(array $classNames): string
    {
        if (empty($classNames)) {
            return '.*';
        }

        $escapedClasses = array_map(
            static fn (string $class): string => preg_quote($class, '~'),
            $classNames
        );

        // Matches any class name that is NOT exactly one of the given classes.
        return '^(?!' . implode('$|', $escapedClasses) . '$).*';
    }
}
