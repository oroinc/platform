<?php

declare(strict_types=1);

namespace Oro\Bundle\SSOBundle\Tests\Unit\Security;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\AbstractOAuthToken;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Http\Authenticator\Passport\SelfValidatedOAuthPassport;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMapInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesser;
use Oro\Bundle\SSOBundle\Security\OAuthAuthenticator;
use Oro\Bundle\SSOBundle\Security\OAuthToken;
use Oro\Bundle\SSOBundle\Security\OAuthTokenFactory;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;

class OAuthAuthenticatorTest extends TestCase
{
    private OAuthAuthenticator $authenticator;
    private MockObject|HttpUtils $httpUtils;
    private MockObject|ResourceOwnerMapInterface $resourceOwnerMap;

    protected function setUp(): void
    {
        $this->httpUtils = $this->createMock(HttpUtils::class);
        $this->userProvider = $this->createMock(OAuthAwareUserProviderInterface::class);
        $this->resourceOwnerMap = $this->createMock(ResourceOwnerMapInterface::class);
        $this->successHandler = $this->createMock(AuthenticationSuccessHandlerInterface::class);
        $this->failureHandler = $this->createMock(AuthenticationFailureHandlerInterface::class);
        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
        $this->organizationGuesser = new OrganizationGuesser();
        $this->tokenFactory = new OAuthTokenFactory();

        $this->authenticator = new OAuthAuthenticator(
            $this->httpUtils,
            $this->userProvider,
            $this->resourceOwnerMap,
            ['/check'],
            $this->successHandler,
            $this->failureHandler,
            $this->httpKernel,
            []
        );

        $this->authenticator->setOrganizationGuesser($this->organizationGuesser);
        $this->authenticator->setTokenFactory($this->tokenFactory);
    }

    public function testSupportsReturnsTrueForValidRequestPath()
    {
        $request = $this->createMock(Request::class);
        $this->httpUtils->expects($this->once())->method('checkRequestPath')->willReturn(true);

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForInvalidRequestPath()
    {
        $request = $this->createMock(Request::class);
        $this->httpUtils->expects($this->once())->method('checkRequestPath')->willReturn(false);

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateThrowsExceptionForInvalidResourceOwner()
    {
        $request = $this->createMock(Request::class);
        $this->resourceOwnerMap->expects($this->once())
            ->method('getResourceOwnerByRequest')
            ->with($request)
            ->willReturn([null, '/check']);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No resource owner match the request.');

        $this->authenticator->authenticate($request);
    }

    /**
     * Mostly it is a copy of original test
     *
     * @see https://github.com/hwi/HWIOAuthBundle/blob/master/tests/Security/Http/Authenticator/
     * OAuthAuthenticatorTest.php
     */
    public function testAuthenticate(): void
    {
        $httpUtilsMock = $this->createMock(HttpUtils::class);
        $userProviderMock = $this->createMock(OAuthAwareUserProviderInterface::class);
        $resourceOwnerMock = $this->createMock(ResourceOwnerInterface::class);
        $checkPath = '/oauth/login_check';
        $request = Request::create($checkPath);
        $checkUri = 'http://localhost/oauth/login_check';
        $accessToken = [
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'expires_in' => '777',
            'oauth_token_secret' => 'secret',
        ];
        $userResponseMock = $this->createMock(UserResponseInterface::class);

        $user = new User();
        $organization = new Organization();
        $user->setOrganization($organization);
        $organization->addUser($user);
        $user->setUsername('test');
        $resourceOwnerName = 'google';

        $httpUtilsMock->expects($this->once())
            ->method('checkRequestPath')
            ->with($request, $checkPath)
            ->willReturn(true);

        $serviceLocator = $this->createMock(ServiceLocator::class);
        $serviceLocator->expects($this->exactly(2))
            ->method('get')
            ->with($resourceOwnerName)
            ->willReturn($resourceOwnerMock);

        $resourceOwnerMap = $this->getResourceOwnerMap(
            [$resourceOwnerName => $checkPath],
            $httpUtilsMock,
            $serviceLocator
        );

        $resourceOwnerMock->expects($this->once())
            ->method('handles')
            ->with($request)
            ->willReturn(true);

        $resourceOwnerMock->expects($this->once())
            ->method('isCsrfTokenValid')
            ->with(null);

        $httpUtilsMock->expects($this->once())
            ->method('createRequest')
            ->with($request, $checkPath)
            ->willReturn(Request::create($checkUri));

        $resourceOwnerMock->expects($this->once())
            ->method('getAccessToken')
            ->with($request, $checkUri)
            ->willReturn($accessToken);

        $resourceOwnerMock->expects($this->once())
            ->method('getUserInformation')
            ->with($accessToken)
            ->willReturn($userResponseMock);

        $userProviderMock->expects($this->once())
            ->method('loadUserByOAuthUserResponse')
            ->with($userResponseMock)
            ->willReturn($user);

        $resourceOwnerMock->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn($resourceOwnerName);

        $authenticator = new OAuthAuthenticator(
            $httpUtilsMock,
            $userProviderMock,
            $resourceOwnerMap,
            [],
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            $this->createMock(HttpKernelInterface::class),
            []
        );

        $authenticator->setTokenFactory(new OAuthTokenFactory());
        $authenticator->setOrganizationGuesser(new OrganizationGuesser());

        $passport = $authenticator->authenticate($request);
        $this->assertInstanceOf(SelfValidatedOAuthPassport::class, $passport);
        $this->assertEquals($user, $passport->getUser());

        /** @var AbstractOAuthToken $token */
        $token = $authenticator->createToken($passport, 'main');
        $this->assertInstanceOf(OAuthToken::class, $token);
        $this->assertEquals($resourceOwnerName, $token->getResourceOwnerName());
        $this->assertEquals($user, $token->getUser());
        $this->assertEquals($organization, $token->getOrganization());
        $this->assertEquals('refresh_token', $token->getRefreshToken());
    }

    private function getResourceOwnerMap(
        array $resources = [],
        $httpUtils = null,
        $serviceLocator = null
    ): ResourceOwnerMap {
        return new ResourceOwnerMap(
            $httpUtils ?: $this->createMock(HttpUtils::class),
            $resources,
            $resources,
            $serviceLocator ?: $this->createMock(ServiceLocator::class)
        );
    }
}
