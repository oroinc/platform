<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\Security\Http\Firewall;

use Oro\Bundle\WsseAuthenticationBundle\Security\Http\Firewall\WsseAuthenticationListener;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseToken;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseTokenFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
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

    private RequestEvent|\PHPUnit\Framework\MockObject\MockObject $responseEvent;

    private Request $request;

    private TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject $tokenStorage;

    private AuthenticationManagerInterface|\PHPUnit\Framework\MockObject\MockObject $authenticationManager;

    private WsseTokenFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $wsseTokenFactory;

    private AuthenticationEntryPointInterface|\PHPUnit\Framework\MockObject\MockObject $authenticationEntryPoint;

    private WsseAuthenticationListener $listener;

    protected function setUp(): void
    {
        $this->responseEvent = $this->createMock(RequestEvent::class);
        $this->request = Request::create('/sample/uri');
        $this->responseEvent->expects(self::once())
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
        $this->tokenStorage->expects(self::never())
            ->method('setToken');

        $this->request->headers->add($header);

        ($this->listener)($this->responseEvent);
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

        $this->authenticationManager->expects(self::once())
            ->method('authenticate')
            ->with($token)
            ->willReturn($tokenMock2 = $this->createMock(WsseToken::class));

        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with($tokenMock2);

        $this->request->headers->add(['X-WSSE' => self::WSSE]);

        ($this->listener)($this->responseEvent);
    }

    private function mockWsseTokenFactory(): WsseToken
    {
        $token = new WsseToken(self::USERNAME, self::PASSWORD, self::PROVIDER_KEY);
        $token->setAttribute('nonce', self::NONCE);
        $token->setAttribute('created', self::CREATED);

        $this->wsseTokenFactory->expects(self::once())
            ->method('create')
            ->with(self::USERNAME, self::PASSWORD, self::PROVIDER_KEY)
            ->willReturn($token);

        return $token;
    }

    public function testHandleWhenReturnResponse(): void
    {
        $token = $this->mockWsseTokenFactory();

        $this->authenticationManager->expects(self::once())
            ->method('authenticate')
            ->with($token)
            ->willReturn($response = new Response());

        $this->responseEvent->expects(self::once())
            ->method('setResponse')
            ->with($response);

        $this->request->headers->add(['X-WSSE' => self::WSSE]);

        ($this->listener)($this->responseEvent);
    }

    public function testHandleWhenException(): void
    {
        $token = $this->mockWsseTokenFactory();

        $this->authenticationManager->expects(self::once())
            ->method('authenticate')
            ->with($token)
            ->willThrowException($exception = new AuthenticationException($msg = 'sample exception'));

        $this->authenticationEntryPoint->expects(self::once())
            ->method('start')
            ->with($this->request, $exception)
            ->willReturn($response = new Response($msg));

        $this->responseEvent->expects(self::once())
            ->method('setResponse')
            ->with($response);

        $this->request->headers->add(['X-WSSE' => self::WSSE]);

        ($this->listener)($this->responseEvent);
    }
}
