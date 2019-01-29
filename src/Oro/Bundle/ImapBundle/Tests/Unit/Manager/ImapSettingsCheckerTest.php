<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapSettingsChecker;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

class ImapSettingsCheckerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImapConnectorFactory|\PHPUnit_Framework_MockObject_MockObject */
    private $connectorFactory;

    /** @var Mcrypt|\PHPUnit_Framework_MockObject_MockObject */
    private $mcrypt;

    /** @var ImapSettingsChecker */
    private $checker;

    protected function setUp()
    {
        $this->connectorFactory = $this->createMock(ImapConnectorFactory::class);
        $this->mcrypt = $this->createMock(Mcrypt::class);
        $this->checker = new ImapSettingsChecker(
            $this->connectorFactory,
            $this->mcrypt
        );
    }

    public function testCheckConnectionError()
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

    public function testCheckConnection()
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

    /**
     * @param UserEmailOrigin $value
     * @param string $decryptedPassword
     */
    private function mockDecryptedPassword(UserEmailOrigin $value, string $decryptedPassword)
    {
        $this->mcrypt->expects($this->once())
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
