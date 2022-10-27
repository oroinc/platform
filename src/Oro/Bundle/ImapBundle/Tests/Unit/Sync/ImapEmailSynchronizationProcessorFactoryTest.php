<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFolderManagerFactory;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Sync\ImapEmailRemoveManager;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizationProcessor;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizationProcessorFactory;
use Psr\Log\LoggerInterface;

class ImapEmailSynchronizationProcessorFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $imapEmailFolderManagerFactory = $this->createMock(ImapEmailFolderManagerFactory::class);
        $em = $this->createMock(EntityManager::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $emailEntityBuilder = $this->createMock(EmailEntityBuilder::class);
        $emailManager = $this->createMock(ImapEmailManager::class);
        $removeManager = $this->createMock(ImapEmailRemoveManager::class);
        $knownEmailAddressChecker = $this->createMock(KnownEmailAddressCheckerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $doctrine->expects($this->exactly(2))
            ->method('getManager')
            ->with(null)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('isOpen')
            ->willReturn(false);
        $doctrine->expects($this->once())
            ->method('resetManager');

        $factory = new ImapEmailSynchronizationProcessorFactory(
            $doctrine,
            $emailEntityBuilder,
            $removeManager,
            $imapEmailFolderManagerFactory
        );

        $factory->setLogger($logger);

        $result = $factory->create($emailManager, $knownEmailAddressChecker);
        $this->assertInstanceOf(ImapEmailSynchronizationProcessor::class, $result);
    }
}
