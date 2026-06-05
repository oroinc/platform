<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapSettingsChecker;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Logger\BufferingLogger;

class ImapSettingsCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImapConnectorFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $connectorFactory;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $encryptor;

    /** @var BufferingLogger */
    private $logger;

    /** @var ImapSettingsChecker */
    private $checker;

    protected function setUp(): void
    {
        $this->connectorFactory = $this->createMock(ImapConnectorFactory::class);
        $this->encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $this->logger = new BufferingLogger();

        $this->checker = new ImapSettingsChecker(
            $this->connectorFactory,
            $this->encryptor
        );
        $this->checker->setLogger($this->logger);
    }

    public function testConnectionCheckDurationSetterAndGetter()
    {
        self::assertNull($this->checker->getConnectionCheckDuration());

        $this->checker->setConnectionCheckDuration(123);
        self::assertSame(123, $this->checker->getConnectionCheckDuration());

        $this->checker->setConnectionCheckDuration(0);
        self::assertNull($this->checker->getConnectionCheckDuration());

        $this->checker->setConnectionCheckDuration(123);
        self::assertSame(123, $this->checker->getConnectionCheckDuration());

        $this->checker->setConnectionCheckDuration(null);
        self::assertNull($this->checker->getConnectionCheckDuration());
    }

    public function testSetConnectionCheckDurationWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The duration cannot be a negative number.');
        $this->checker->setConnectionCheckDuration(-1);
    }

    public function testCheckConnectionError()
    {
        $exception = new \Exception('Test error message');

        $value = new UserEmailOrigin();
        $decryptedPassword = 'decrypted_password';

        $this->encryptor->expects($this->once())
            ->method('decryptData')
            ->with($value->getPassword())
            ->willReturn($decryptedPassword);

        $config = new ImapConfig(
            $value->getImapHost(),
            $value->getImapPort(),
            $value->getImapEncryption(),
            $value->getUser(),
            $decryptedPassword
        );

        $connector = $this->createMock(ImapConnector::class);
        $this->connectorFactory->expects($this->once())
            ->method('createImapConnector')
            ->with($config)
            ->willReturn($connector);
        $connector->expects($this->once())
            ->method('getCapability')
            ->willThrowException($exception);

        $this->assertFalse($this->checker->checkConnection($value));

        self::assertEquals(
            [
                ['info', 'Checking IMAP connection ...', ['host' => null, 'port' => null]],
                [
                    'error',
                    'Could not establish IMAP connection.',
                    ['host' => null, 'port' => null, 'exception' => $exception]
                ]
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testCheckConnection()
    {
        $value = new UserEmailOrigin();
        $decryptedPassword = 'decrypted_password';

        $this->encryptor->expects($this->once())
            ->method('decryptData')
            ->with($value->getPassword())
            ->willReturn($decryptedPassword);

        $config = new ImapConfig(
            $value->getImapHost(),
            $value->getImapPort(),
            $value->getImapEncryption(),
            $value->getUser(),
            $decryptedPassword
        );

        $connector = $this->createMock(ImapConnector::class);
        $this->connectorFactory->expects($this->once())
            ->method('createImapConnector')
            ->with($config)
            ->willReturn($connector);
        $connector->expects($this->once())
            ->method('getCapability')
            ->willReturn([]);

        $this->assertTrue($this->checker->checkConnection($value));

        self::assertEquals(
            [
                ['info', 'Checking IMAP connection ...', ['host' => null, 'port' => null]],
                ['info', 'IMAP connection was successfully established.', ['host' => null, 'port' => null]]
            ],
            $this->logger->cleanLogs()
        );
    }
}
