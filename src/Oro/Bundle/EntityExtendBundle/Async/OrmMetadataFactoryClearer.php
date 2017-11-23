<?php

namespace Oro\Bundle\EntityExtendBundle\Async;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events as OrmEvents;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\IntrospectableContainerInterface;
use Symfony\Bridge\Doctrine\ContainerAwareEventManager;

use Oro\Component\PhpUtils\ReflectionUtil;
use Oro\Bundle\EntityBundle\ORM\OroClassMetadataFactory;
use Oro\Bundle\EntityBundle\ORM\Repository\EntityRepositoryFactory;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ClearerInterface;

/**
 * Removes unnecessary ORM metadata factory to avoid keeping unnecesarry objects in the memory.
 */
class OrmMetadataFactoryClearer implements ClearerInterface
{
    /** @var ContainerInterface|IntrospectableContainerInterface */
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
            if ($metadataFactory instanceof OroClassMetadataFactory && !$metadataFactory->isDisconected()) {
                $em = $metadataFactory->getEntityManager();
                if ($em instanceof EntityManager) {
                    if ($em->isOpen()) {
                        $logger->info('Disconect ORM metadata factory');
                        $this->disconnectEntityManager($em, $logger);
                        $metadataFactory->setDisconected(true);
                    }
                } else {
                    $logger->warning(sprintf(
                        'Cannot disconect ORM metadata factory due to unexpected type of the EntityManager (%s)',
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
        $this->clearConnection($em, $logger);
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
        // all events except "loadClassMetadata" and "onClassMetadataNotFound"
        $eventsToKeep = [
            OrmEvents::loadClassMetadata,
            OrmEvents::onClassMetadataNotFound
        ];

        $eventManager = $em->getEventManager();
        if ($eventManager instanceof ContainerAwareEventManager) {
            $property = ReflectionUtil::getProperty(new \ReflectionClass($eventManager), 'listeners');
            if (null !== $property) {
                $property->setAccessible(true);
                $listeners = $property->getValue($eventManager);
                if (is_array($listeners)) {
                    $eventNames = array_keys($listeners);
                    foreach ($eventNames as $eventName) {
                        if (!in_array($eventName, $eventsToKeep, true)) {
                            unset($listeners[$eventName]);
                        }
                    }
                    $property->setValue($eventManager, $listeners);
                } else {
                    $logger->warning('The EventManager "listeners" property should be an array');
                }
            } else {
                $logger->warning('The EventManager does not have "listeners" property');
            }
        }

        $em->getConfiguration()->getEntityListenerResolver()->clear();
    }

    /**
     * @param EntityManager   $em
     * @param LoggerInterface $logger
     */
    private function clearConnection(EntityManager $em, LoggerInterface $logger)
    {
        $connectionProperty = ReflectionUtil::getProperty(new \ReflectionClass($em), 'conn');
        if (null !== $connectionProperty) {
            $connectionProperty->setAccessible(true);
            $connection = $connectionProperty->getValue($em);
            if (null !== $connection) {
                // make sure that the connection is closed
                if ($connection instanceof Connection && $connection->isConnected()) {
                    $connection->close();
                }
                // remove a reference to the connection from the entoty manager
                $connectionProperty->setValue($em, null);
            }
        } else {
            $logger->warning('The EntityManager does not have "conn" property');
        }
    }
}
