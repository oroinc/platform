<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapSettingsChecker;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImapSettingsCheckerTest extends TestCase
{
    private ImapConnectorFactory&MockObject $connectorFactory;
    private SymmetricCrypterInterface&MockObject $encryptor;
    private ImapSettingsChecker $checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->connectorFactory = $this->createMock(ImapConnectorFactory::class);
        $this->encryptor = $this->createMock(SymmetricCrypterInterface::class);
        $this->checker = new ImapSettingsChecker(
            $this->connectorFactory,
            $this->encryptor
        );
    }

    public function testCheckConnectionError(): void
    {
        $value = new UserEmailOrigin();
        $decryptedPassword = 'decrypted_password';

        $this->mockDecryptedPassword($value, $decryptedPassword);

        $config = $this->getImapConfig($value, $decryptedPassword);

        $connector = $this->createMock(ImapConnector::class);
        $connector->expects($this->once())
            ->method('getCapability')
            ->willThrowException(new \Exception('Test error message'));
        $this->connectorFactory->expects($this->once())
            ->method('createImapConnector')
            ->with($config)
            ->willReturn($connector);

        $this->assertFalse($this->checker->checkConnection($value));
    }

    public function testCheckConnection(): void
    {
        $value = new UserEmailOrigin();
        $decryptedPassword = 'decrypted_password';

        $this->mockDecryptedPassword($value, $decryptedPassword);

        $config = $this->getImapConfig($value, $decryptedPassword);

        $connector = $this->createMock(ImapConnector::class);
        $this->connectorFactory->expects($this->once())
            ->method('createImapConnector')
            ->with($config)
            ->willReturn($connector);

        $this->assertTrue($this->checker->checkConnection($value));
    }

    private function mockDecryptedPassword(UserEmailOrigin $value, string $decryptedPassword)
    {
        $this->encryptor->expects($this->once())
            ->method('decryptData')
            ->with($value->getPassword())
            ->willReturn($decryptedPassword);
    }

    /**
     * @param UserEmailOrigin $value
     * @param string $decryptedPassword
     *
     * @return ImapConfig
     */
    private function getImapConfig(UserEmailOrigin $value, string $decryptedPassword)
    {
        return new ImapConfig(
            $value->getImapHost(),
            $value->getImapPort(),
            $value->getImapEncryption(),
            $value->getUser(),
            $decryptedPassword
        );
    }
}
