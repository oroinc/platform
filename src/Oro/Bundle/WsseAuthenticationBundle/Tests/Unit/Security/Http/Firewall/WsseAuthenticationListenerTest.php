<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\Security\Http\Firewall;

use Oro\Bundle\WsseAuthenticationBundle\Security\Http\Firewall\WsseAuthenticationListener;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseToken;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseTokenFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class WsseAuthenticationListenerTest extends \PHPUnit\Framework\TestCase
{
    private const WSSE = 'UsernameToken Username="someuser", PasswordDigest="somedigest", Nonce="somenonce", Created='
    . '"2010-12-12 20:00:00"';
    private const USERNAME = 'someuser';
    private const PASSWORD = 'somedigest';
    private const CREATED = '2010-12-12 20:00:00';
    private const NONCE = 'somenonce';
    private const PROVIDER_KEY = 'someproviderkey';

    /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $responseEvent;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var AuthenticationManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authenticationManager;

    /** @var WsseTokenFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $wsseTokenFactory;

    /** @var AuthenticationEntryPointInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authenticationEntryPoint;

    /** @var WsseAuthenticationListener */
    private $listener;

    protected function setUp(): void
    {
        $this->responseEvent = $this->createMock(GetResponseEvent::class);
        $this->request = $this->getMockForAbstractClass(Request::class);
        $this->responseEvent
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->authenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $this->wsseTokenFactory = $this->createMock(WsseTokenFactoryInterface::class);
        $this->authenticationEntryPoint = $this->createMock(AuthenticationEntryPointInterface::class);

        $this->listener = new WsseAuthenticationListener(
            $this->tokenStorage,
            $this->authenticationManager,
            $this->wsseTokenFactory,
            $this->authenticationEntryPoint,
            self::PROVIDER_KEY
        );
    }

    /**
     * @dataProvider handleWhenNoHeaderDataProvider
     */
    public function testHandleWhenNoHeader(array $header): void
    {
        $this->tokenStorage
            ->expects($this->never())
            ->method('setToken');

        $this->request->headers->add($header);

        $this->listener->handle($this->responseEvent);
    }

    public function handleWhenNoHeaderDataProvider(): array
    {
        return [
            ['header' => ['X-WSSE' => 'invalid header']],
            ['header' => []],
        ];
    }

    public function testHandleWhenReturnToken(): void
    {
        $token = $this->mockWsseTokenFactory();

        $this->authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($token)
            ->willReturn($tokenMock2 = $this->createMock(WsseToken::class));

        $this->tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($tokenMock2);

        $this->request->headers->add(['X-WSSE' => self::WSSE]);

        $this->listener->handle($this->responseEvent);
    }

    private function mockWsseTokenFactory(): WsseToken
    {
        $token = new WsseToken(self::USERNAME, self::PASSWORD, self::PROVIDER_KEY);
        $token->setAttribute('nonce', self::NONCE);
        $token->setAttribute('created', self::CREATED);

        $this->wsseTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with(self::USERNAME, self::PASSWORD, self::PROVIDER_KEY)
            ->willReturn($token);

        return $token;
    }

    public function testHandleWhenReturnResponse(): void
    {
        $token = $this->mockWsseTokenFactory();

        $this->authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($token)
            ->willReturn($response = new Response());

        $this->responseEvent
            ->expects($this->once())
            ->method('setResponse')
            ->with($response);

        $this->request->headers->add(['X-WSSE' => self::WSSE]);

        $this->listener->handle($this->responseEvent);
    }

    public function testHandleWhenException(): void
    {
        $token = $this->mockWsseTokenFactory();

        $this->authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($token)
            ->willThrowException($exception = new AuthenticationException($msg = 'sample exception'));

        $this->authenticationEntryPoint
            ->expects($this->once())
            ->method('start')
            ->with($this->request, $exception)
            ->willReturn($response = new Response($msg));

        $this->responseEvent
            ->expects($this->once())
            ->method('setResponse')
            ->with($response);

        $this->request->headers->add(['X-WSSE' => self::WSSE]);

        $this->listener->handle($this->responseEvent);
    }
}
