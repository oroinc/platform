<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\Security\Http\EntryPoint;

use Oro\Bundle\WsseAuthenticationBundle\Security\Http\EntryPoint\WsseEntryPoint;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class WsseEntryPointTest extends \PHPUnit\Framework\TestCase
{
    private const REALM_NAME = 'sample-realm';
    private const PROFILE = 'sample-profile';

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var WsseEntryPoint */
    private $entryPoint;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(Request::class);

        $this->entryPoint = new WsseEntryPoint($this->logger, self::REALM_NAME, self::PROFILE);
    }

    public function testStart(): void
    {
        $authenticationException = new AuthenticationException($msg = 'sample-message');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($msg);

        $response = $this->entryPoint->start($this->request, $authenticationException);

        $this->assertEquals(401, $response->getStatusCode());

        $this->assertMatchesRegularExpression(
            sprintf('/^WSSE realm="%s", profile="%s"$/', self::REALM_NAME, self::PROFILE),
            $response->headers->get('WWW-Authenticate')
        );
    }

    public function testStartWithNoException(): void
    {
        $this->logger->expects($this->never())
            ->method('warning');

        $response = $this->entryPoint->start($this->request);

        $this->assertEquals(401, $response->getStatusCode());

        $this->assertMatchesRegularExpression(
            sprintf('/^WSSE realm="%s", profile="%s"$/', self::REALM_NAME, self::PROFILE),
            $response->headers->get('WWW-Authenticate')
        );
    }
}
