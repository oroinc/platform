<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Async;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Configuration as DbalConfiguration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\Configuration as OrmConfiguration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events as OrmEvents;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Oro\Bundle\EntityBundle\ORM\OroClassMetadataFactory;
use Oro\Bundle\EntityBundle\ORM\Repository\EntityRepositoryFactory;
use Oro\Bundle\EntityExtendBundle\Async\OrmMetadataFactoryClearer;
use Oro\Component\PhpUtils\ReflectionUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ContainerAwareEventManager;
use Symfony\Component\DependencyInjection\Container;

class OrmMetadataFactoryClearerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|Container */
    private $container;

    /** @var OrmMetadataFactoryClearer */
    private $clearer;

    protected function setUp()
    {
        $this->container = $this->createMock(Container::class);

        $this->clearer = new OrmMetadataFactoryClearer(
            $this->container,
            'foo_metadata_factory'
        );
    }

    /**
     * @param object $object
     * @param string $propertyName
     * @param mixed  $propertyValue
     */
    private function setPrivateProperty($object, $propertyName, $propertyValue)
    {
        $property = ReflectionUtil::getProperty(new \ReflectionClass($object), $propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $propertyValue);
    }

    public function testShouldNotGetUninitializedMetadataFactoryFromContainer()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(self::never())
            ->method('info');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(false);
        $this->container->expects(self::never())
            ->method('get');

        $this->clearer->clear($logger);
    }

    public function testShouldSkipMetadataFactoryIfItIsNotInstanceOfOroClassMetadataFactoryClass()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);

        $logger->expects(self::never())
            ->method('info');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);

        $this->clearer->clear($logger);
    }

    public function testShouldSkipAlreadyDisconnectedMetadataFactory()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);

        $logger->expects(self::never())
            ->method('info');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(true);

        $this->clearer->clear($logger);
    }

    public function testShouldLogWarningIfUnexpectedTypeOfEntityManager()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $logger->expects(self::never())
            ->method('info');
        $logger->expects(self::once())
            ->method('warning')
            ->with(sprintf(
                'Cannot disconnect ORM metadata factory due to unexpected type of the EntityManager (%s)',
                get_class($em)
            ));

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->clearer->clear($logger);
    }

    public function testShouldSkipClosedEntityManager()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);

        $logger->expects(self::never())
            ->method('info');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(false);

        $this->clearer->clear($logger);
    }

    public function testShouldCloseEntityManagerIfItIsOpen()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn(new DbalConfiguration());
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);
        $connection->expects(self::once())
            ->method('close');
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconnect ORM metadata factory');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new OrmConfiguration());
        $em->expects(self::once())
            ->method('close');

        $this->clearer->clear($logger);
    }

    public function testShouldClearRepositoryFactoryIfItIsInstanceOfEntityRepositoryFactoryClass()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $configuration = new OrmConfiguration();
        $repositoryFactory = $this->createMock(EntityRepositoryFactory::class);
        $configuration->setRepositoryFactory($repositoryFactory);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn(new DbalConfiguration());
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);
        $connection->expects(self::once())
            ->method('close');
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconnect ORM metadata factory');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);
        $repositoryFactory->expects(self::once())
            ->method('clear');

        $this->clearer->clear($logger);
    }

    public function testShouldRemoveUnneededListenersIfEventManagerIsInstanceOfContainerAwareEventManagerClass()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $eventManager = new ContainerAwareEventManager($this->container);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn(new DbalConfiguration());
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);
        $connection->expects(self::once())
            ->method('close');
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        // guard
        self::assertObjectHasAttribute('listeners', $eventManager);
        self::assertObjectHasAttribute('initialized', $eventManager);

        $eventManager->addEventListener(
            [OrmEvents::onFlush, OrmEvents::loadClassMetadata, OrmEvents::onClassMetadataNotFound],
            'foo_listener'
        );
        $this->setPrivateProperty(
            $eventManager,
            'initialized',
            [
                OrmEvents::onFlush                 => true,
                OrmEvents::loadClassMetadata       => true,
                OrmEvents::onClassMetadataNotFound => true
            ]
        );

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconnect ORM metadata factory');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new OrmConfiguration());
        $em->expects(self::once())
            ->method('getEventManager')
            ->willReturn($eventManager);

        $this->clearer->clear($logger);

        self::assertAttributeEquals(
            [
                OrmEvents::loadClassMetadata       => [
                    '_service_foo_listener' => 'foo_listener'
                ],
                OrmEvents::onClassMetadataNotFound => [
                    '_service_foo_listener' => 'foo_listener'
                ]
            ],
            'listeners',
            $eventManager
        );
        self::assertAttributeEquals(
            [
                OrmEvents::loadClassMetadata       => true,
                OrmEvents::onClassMetadataNotFound => true
            ],
            'initialized',
            $eventManager
        );
    }

    public function testShouldLogWarningIfListenersPropertyOfEventManagerIsNotArray()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $eventManager = new ContainerAwareEventManager($this->container);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn(new DbalConfiguration());
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);
        $connection->expects(self::once())
            ->method('close');
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        // guard
        self::assertObjectHasAttribute('listeners', $eventManager);
        self::assertObjectHasAttribute('initialized', $eventManager);

        $this->setPrivateProperty($eventManager, 'listeners', new ArrayCollection());
        $this->setPrivateProperty($eventManager, 'initialized', []);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconnect ORM metadata factory');
        $logger->expects(self::once())
            ->method('warning')
            ->with('The EventManager "listeners" and "initialized" properties should be an array');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new OrmConfiguration());
        $em->expects(self::once())
            ->method('getEventManager')
            ->willReturn($eventManager);

        $this->clearer->clear($logger);
    }

    public function testShouldLogWarningIfInitializedPropertyOfEventManagerIsNotArray()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $eventManager = new ContainerAwareEventManager($this->container);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn(new DbalConfiguration());
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);
        $connection->expects(self::once())
            ->method('close');
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        // guard
        self::assertObjectHasAttribute('listeners', $eventManager);
        self::assertObjectHasAttribute('initialized', $eventManager);

        $this->setPrivateProperty($eventManager, 'listeners', []);
        $this->setPrivateProperty($eventManager, 'initialized', new ArrayCollection());

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconnect ORM metadata factory');
        $logger->expects(self::once())
            ->method('warning')
            ->with('The EventManager "listeners" and "initialized" properties should be an array');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new OrmConfiguration());
        $em->expects(self::once())
            ->method('getEventManager')
            ->willReturn($eventManager);

        $this->clearer->clear($logger);
    }

    public function testShouldClearEntityListenerResolver()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $configuration = new OrmConfiguration();
        $entityListenerResolver = $this->createMock(EntityListenerResolver::class);
        $configuration->setEntityListenerResolver($entityListenerResolver);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn(new DbalConfiguration());
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);
        $connection->expects(self::once())
            ->method('close');
        $em->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconnect ORM metadata factory');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityListenerResolver->expects(self::once())
            ->method('clear');

        $this->clearer->clear($logger);
    }

    public function testShouldNotCloseConnectionIfItIsAlreadyClosed()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $connection = $this->createMock(Connection::class);
        $em->expects(self::once())->method('getConnection')->willReturn($connection);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconnect ORM metadata factory');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new OrmConfiguration());
        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn(new DbalConfiguration());
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);
        $connection->expects(self::never())
            ->method('close');

        $this->clearer->clear($logger);
    }

    public function testShouldCloseConnectionIfItIsOpen()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $connection = $this->createMock(Connection::class);

        $em->expects(self::once())->method('getConnection')->willReturn($connection);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconnect ORM metadata factory');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new OrmConfiguration());
        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn(new DbalConfiguration());
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);
        $connection->expects(self::once())
            ->method('close');

        $this->clearer->clear($logger);
    }

    public function testShouldRemoveSqlLoggerFromConnection()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $connection = $this->createMock(Connection::class);
        $configuration = new DbalConfiguration();
        $configuration->setSQLLogger($this->createMock(SQLLogger::class));

        $em->expects(self::once())->method('getConnection')->willReturn($connection);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconnect ORM metadata factory');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new OrmConfiguration());
        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->clearer->clear($logger);

        self::assertNull($configuration->getSQLLogger());
    }

    public function testShouldRemoveTransactionWatcherFromConnection()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $connection = $this->createMock(ConnectionWithTransactionWatcher::class);

        $em->expects(self::once())->method('getConnection')->willReturn($connection);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconnect ORM metadata factory');
        $logger->expects(self::never())
            ->method('warning');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconnected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new OrmConfiguration());
        $connection->expects(self::once())
            ->method('getConfiguration')
            ->willReturn(new DbalConfiguration());
        $connection->expects(self::once())
            ->method('setTransactionWatcher')
            ->with(self::isNull());

        $this->clearer->clear($logger);
    }
}
