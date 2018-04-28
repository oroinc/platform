<?php

namespace Oro\Bundle\EntityExtendBundle\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events as OrmEvents;
use Oro\Bundle\EntityBundle\ORM\OroClassMetadataFactory;
use Oro\Bundle\EntityBundle\ORM\Repository\EntityRepositoryFactory;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ClearerInterface;
use Oro\Component\DoctrineUtils\DBAL\TransactionWatcherAwareInterface;
use Oro\Component\PhpUtils\ReflectionUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ContainerAwareEventManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Removes unnecessary ORM metadata factory to avoid keeping unnecessary objects in the memory.
 */
class OrmMetadataFactoryClearer implements ClearerInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var string */
    private $metadataFactoryServiceId;

    /**
     * @param ContainerInterface $container
     * @param string             $metadataFactoryServiceId
     */
    public function __construct(ContainerInterface $container, $metadataFactoryServiceId)
    {
        $this->container = $container;
        $this->metadataFactoryServiceId = $metadataFactoryServiceId;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(LoggerInterface $logger)
    {
        if ($this->container->initialized($this->metadataFactoryServiceId)) {
            $metadataFactory = $this->container->get($this->metadataFactoryServiceId);
            if ($metadataFactory instanceof OroClassMetadataFactory && !$metadataFactory->isDisconnected()) {
                $em = $metadataFactory->getEntityManager();
                if ($em instanceof EntityManager) {
                    if ($em->isOpen()) {
                        $logger->info('Disconnect ORM metadata factory');
                        $this->disconnectEntityManager($em, $logger);
                        $metadataFactory->setDisconnected(true);
                    }
                } else {
                    $logger->warning(sprintf(
                        'Cannot disconnect ORM metadata factory due to unexpected type of the EntityManager (%s)',
                        is_object($em) ? get_class($em) : gettype($em)
                    ));
                }
            }
        }
    }

    /**
     * @param EntityManager   $em
     * @param LoggerInterface $logger
     */
    private function disconnectEntityManager(EntityManager $em, LoggerInterface $logger)
    {
        $em->close();
        $this->clearRepositoryFactory($em);
        $this->clearEventManager($em, $logger);
        $this->clearConnection($em->getConnection());
    }

    /**
     * @param EntityManager $em
     */
    private function clearRepositoryFactory(EntityManager $em)
    {
        $repositoryFactory = $em->getConfiguration()->getRepositoryFactory();
        if ($repositoryFactory instanceof EntityRepositoryFactory) {
            $repositoryFactory->clear();
        }
    }

    /**
     * @param EntityManager   $em
     * @param LoggerInterface $logger
     */
    private function clearEventManager(EntityManager $em, LoggerInterface $logger)
    {
        $eventsToKeep = [
            OrmEvents::loadClassMetadata,
            OrmEvents::onClassMetadataNotFound
        ];

        $eventManager = $em->getEventManager();
        if ($eventManager instanceof ContainerAwareEventManager) {
            $listenersProperty = ReflectionUtil::getProperty(new \ReflectionClass($eventManager), 'listeners');
            $initializedProperty = ReflectionUtil::getProperty(new \ReflectionClass($eventManager), 'initialized');
            if (null !== $listenersProperty && null !== $initializedProperty) {
                $listenersProperty->setAccessible(true);
                $listeners = $listenersProperty->getValue($eventManager);
                $initializedProperty->setAccessible(true);
                $initialized = $initializedProperty->getValue($eventManager);
                if (is_array($listeners) && is_array($initialized)) {
                    $eventNames = array_keys($listeners);
                    foreach ($eventNames as $eventName) {
                        if (!in_array($eventName, $eventsToKeep, true)) {
                            unset($listeners[$eventName], $initialized[$eventName]);
                        }
                    }
                    $listenersProperty->setValue($eventManager, $listeners);
                    $initializedProperty->setValue($eventManager, $initialized);
                } else {
                    $logger->warning('The EventManager "listeners" and "initialized" properties should be an array');
                }
            } else {
                $logger->warning('The EventManager does not have "listeners" and "initialized" properties');
            }
        }

        $em->getConfiguration()->getEntityListenerResolver()->clear();
    }

    /**
     * @param Connection $connection
     */
    private function clearConnection(Connection $connection)
    {
        // make sure that the connection is closed
        if ($connection->isConnected()) {
            $connection->close();
        }
        // remove the SQL logger
        $connection->getConfiguration()->setSQLLogger(null);
        // remove the transaction watcher
        if ($connection instanceof TransactionWatcherAwareInterface) {
            $connection->setTransactionWatcher(null);
        }
    }
}
