<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\Security\Http\EntryPoint;

use Oro\Bundle\WsseAuthenticationBundle\Security\Http\EntryPoint\WsseEntryPoint;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class EntryPointTest extends \PHPUnit\Framework\TestCase
{
    private const REALM = 'TheRealm';
    private const PROFILE = 'TheProfile';

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

        $this->entryPoint = new WsseEntryPoint($this->logger, self::REALM, self::PROFILE);
    }

    public function testStart(): void
    {
        $authenticationException = new AuthenticationException('TheAuthenticationExceptionMessage');

        $response = $this->entryPoint->start($this->request, $authenticationException);

        $this->assertEquals(401, $response->getStatusCode());

        $this->assertMatchesRegularExpression(
            sprintf(
                '/^WSSE realm="%s", profile="%s"$/',
                self::REALM,
                self::PROFILE
            ),
            $response->headers->get('WWW-Authenticate')
        );
    }

    public function testStartWithNoException(): void
    {
        $response = $this->entryPoint->start($this->request);

        $this->assertEquals(401, $response->getStatusCode());

        $this->assertMatchesRegularExpression(
            sprintf(
                '/^WSSE realm="%s", profile="%s"$/',
                self::REALM,
                self::PROFILE
            ),
            $response->headers->get('WWW-Authenticate')
        );
    }
}
