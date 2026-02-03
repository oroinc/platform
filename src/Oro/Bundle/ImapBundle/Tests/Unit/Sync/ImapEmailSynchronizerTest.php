<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Sync;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;
use Oro\Bundle\EmailBundle\Sync\NotificationAlertManager;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizationProcessorFactory;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizer;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImapEmailSynchronizerTest extends TestCase
{
    private ManagerRegistry|MockObject $doctrine;
    private KnownEmailAddressCheckerFactory|MockObject $knownEmailAddressCheckerFactory;
    private ImapEmailSynchronizationProcessorFactory|MockObject $syncProcessorFactory;
    private ImapConnectorFactory|MockObject $connectorFactory;
    private SymmetricCrypterInterface|MockObject $encryptor;
    private OAuthManagerRegistry|MockObject $oauthManagerRegistry;
    private NotificationAlertManager|MockObject $alertManager;
    private ImapEmailSynchronizer $imapEmailSynchronizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->knownEmailAddressCheckerFactory = $this->createMock(KnownEmailAddressCheckerFactory::class);
        $this->syncProcessorFactory = $this->createMock(ImapEmailSynchronizationProcessorFactory::class);
        $this->connectorFactory = $this->createMock(ImapConnectorFactory::class);
        $this->encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $this->oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);
        $this->alertManager = $this->createMock(NotificationAlertManager::class);

        $this->imapEmailSynchronizer = $this->getMockBuilder(ImapEmailSynchronizer::class)
            ->setConstructorArgs([
                $this->doctrine,
                $this->knownEmailAddressCheckerFactory,
                $this->syncProcessorFactory,
                $this->connectorFactory,
                $this->encryptor,
                $this->oauthManagerRegistry,
                $this->alertManager,
            ])
            ->onlyMethods([])
            ->getMock();
    }

    public function testAddCredentialFilter(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->exactly(4))
            ->method('andWhere')
            ->withConsecutive(
                [$this->equalTo('o.imapHost IS NOT NULL')],
                [$this->equalTo('o.imapPort > 0')],
                [$this->equalTo('o.user IS NOT NULL')],
                [$this->equalTo('o.password IS NOT NULL OR o.accessToken IS NOT NULL')],
            )
            ->willReturn($queryBuilder);

        $reflection = new \ReflectionClass($this->imapEmailSynchronizer);
        $method = $reflection->getMethod('addCredentialFilter');
        $result = $method->invoke($this->imapEmailSynchronizer, $queryBuilder);

        $this->assertSame($queryBuilder, $result);
    }
}
