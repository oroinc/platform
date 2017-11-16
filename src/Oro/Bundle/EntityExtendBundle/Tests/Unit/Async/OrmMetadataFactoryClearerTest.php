<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Events as OrmEvents;
use Doctrine\ORM\Mapping\EntityListenerResolver;

use Symfony\Bridge\Doctrine\ContainerAwareEventManager;
use Symfony\Component\DependencyInjection\Container;

use Oro\Component\PhpUtils\ReflectionUtil;
use Oro\Bundle\EntityBundle\ORM\OroClassMetadataFactory;
use Oro\Bundle\EntityBundle\ORM\Repository\EntityRepositoryFactory;
use Oro\Bundle\EntityExtendBundle\Async\OrmMetadataFactoryClearer;

class OrmMetadataFactoryClearerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|Container */
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
            ->method('isDisconected')
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
                'Cannot disconect ORM metadata factory due to unexpected type of the EntityManager (%s)',
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
            ->method('isDisconected')
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
            ->method('isDisconected')
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

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconect ORM metadata factory');
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
            ->method('isDisconected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());
        $em->expects(self::once())
            ->method('close');

        $this->clearer->clear($logger);
    }

    public function testShouldClearRepositoryFactoryIfItIsInstanceOfEntityRepositoryFactoryClass()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $configuration = new Configuration();
        $repositoryFactory = $this->createMock(EntityRepositoryFactory::class);
        $configuration->setRepositoryFactory($repositoryFactory);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconect ORM metadata factory');
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
            ->method('isDisconected')
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

    public function testShouldRemoveUnneddedListenersIfEventManagerIsInstanceOfContainerAwareEventManagerClass()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $eventManager = new ContainerAwareEventManager($this->container);
        $eventManager->addEventListener(
            [OrmEvents::onFlush, OrmEvents::loadClassMetadata, OrmEvents::onClassMetadataNotFound],
            'foo_listener'
        );

        // guard
        self::assertObjectHasAttribute('listeners', $eventManager);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconect ORM metadata factory');
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
            ->method('isDisconected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());
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
    }

    public function testShouldLogWarningIfListenersPropertyOfEventManagerIsNotArray()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $eventManager = new ContainerAwareEventManager($this->container);

        // guard
        self::assertObjectHasAttribute('listeners', $eventManager);

        $property = ReflectionUtil::getProperty(new \ReflectionClass($eventManager), 'listeners');
        $property->setAccessible(true);
        $property->setValue($eventManager, new ArrayCollection());

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconect ORM metadata factory');
        $logger->expects(self::once())
            ->method('warning')
            ->with('The EventManager "listeners" property should be an array');

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_metadata_factory')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_metadata_factory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::once())
            ->method('isDisconected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());
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
        $configuration = new Configuration();
        $entityListenerResolver = $this->createMock(EntityListenerResolver::class);
        $configuration->setEntityListenerResolver($entityListenerResolver);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconect ORM metadata factory');
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
            ->method('isDisconected')
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

    public function testShouldRemoveConnectionFromEntityManager()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $connection = $this->createMock(Connection::class);

        $property = ReflectionUtil::getProperty(new \ReflectionClass($em), 'conn');
        $property->setAccessible(true);
        $property->setValue($em, $connection);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconect ORM metadata factory');
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
            ->method('isDisconected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());

        $this->clearer->clear($logger);

        self::assertAttributeSame(null, 'conn', $em);
    }

    public function testShouldNotCloseConnectionIfItIsAlreadyClosed()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $metadataFactory = $this->createMock(OroClassMetadataFactory::class);
        $em = $this->createMock(EntityManager::class);
        $connection = $this->createMock(Connection::class);

        $property = ReflectionUtil::getProperty(new \ReflectionClass($em), 'conn');
        $property->setAccessible(true);
        $property->setValue($em, $connection);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconect ORM metadata factory');
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
            ->method('isDisconected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());
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

        $property = ReflectionUtil::getProperty(new \ReflectionClass($em), 'conn');
        $property->setAccessible(true);
        $property->setValue($em, $connection);

        $logger->expects(self::once())
            ->method('info')
            ->with('Disconect ORM metadata factory');
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
            ->method('isDisconected')
            ->willReturn(false);
        $metadataFactory->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('isOpen')
            ->willReturn(true);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);
        $connection->expects(self::once())
            ->method('close');

        $this->clearer->clear($logger);
    }
}
