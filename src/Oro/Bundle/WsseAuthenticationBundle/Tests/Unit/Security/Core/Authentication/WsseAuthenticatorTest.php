<?php

namespace Oro\Bundle\WsseAuthenticationBundle\Tests\Unit\Security\Core\Authentication;

use Oro\Bundle\ApiBundle\Security\FeatureDependAuthenticatorChecker;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Exception\BadUserOrganizationException;
use Oro\Bundle\SecurityBundle\Model\Role;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Oro\Bundle\WsseAuthenticationBundle\Security\Core\Authentication\WsseAuthenticator;
use Oro\Bundle\WsseAuthenticationBundle\Security\Http\EntryPoint\WsseEntryPoint;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseToken;
use Oro\Bundle\WsseAuthenticationBundle\Security\WsseTokenFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\MessageDigestPasswordHasher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class WsseAuthenticatorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_API_KEY = 'FmPUH4muyTze6PyRAZGGxv5Thag=';
    private const FIREWALL_NAME = 'test_firewall_name';

    private MockObject|UserProviderInterface $userProvider;
    private MockObject|MessageDigestPasswordHasher $encoder;
    private MockObject|WsseTokenFactory $wsseTokenFactory;
    private MockObject|WsseEntryPoint $entryPoint;
    private MockObject|WsseEntryPoint $featureDependAuthenticatorChecker;
    private WsseAuthenticator $wsseAuthenticator;

    protected function setUp(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureDependAuthenticatorChecker = $this->createMock(FeatureDependAuthenticatorChecker::class);
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->encoder = $this->createMock(MessageDigestPasswordHasher::class);
        $this->entryPoint = $this->createMock(WsseEntryPoint::class);
        $this->wsseTokenFactory = $this->createMock(WsseTokenFactory::class);
        $cache = new ArrayAdapter();

        $this->wsseAuthenticator = new WsseAuthenticator(
            $this->featureDependAuthenticatorChecker,
            $tokenStorage,
            $this->wsseTokenFactory,
            $this->userProvider,
            $this->entryPoint,
            self::FIREWALL_NAME,
            $this->encoder,
            $cache
        );
    }

    public function testSupportsSuccess(): void
    {
        $this->featureDependAuthenticatorChecker
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $request = new Request();
        $request->headers = new HeaderBag([
            'X-WSSE' => '\ UsernameToken\ Username="admin",\ PasswordDigest="FmPUH4muyTze6PyRAZGGxv5Thag=",\ '
                . 'Created="2023-10-26T13:06:30+03:00",\ Nonce="eEhwWFozbTVNbWhJMFFBaFNNUE85c3dUK0pvPQ=="'
        ]);
        self::assertTrue($this->wsseAuthenticator->supports($request));
    }

    public function testSupportsEmptyHeaders(): void
    {
        $request = new Request();
        $request->headers = new HeaderBag([]);
        self::assertFalse($this->wsseAuthenticator->supports($request));
    }

    public function testSupportsAbsendSomePart(): void
    {
        $request = new Request();
        // absent Nonce
        $request->headers = new HeaderBag([
            'X-WSSE' => '\ UsernameToken\ Username="admin",\ PasswordDigest="FmPUH4muyTze6PyRAZGGxv5Thag=",\ '
                . 'Created="2023-10-26T13:06:30+03:00"'
        ]);
        self::assertFalse($this->wsseAuthenticator->supports($request));
        // absent Username
        $request->headers = new HeaderBag([
            'X-WSSE' => '\ UsernameToken\ PasswordDigest="FmPUH4muyTze6PyRAZGGxv5Thag=",\ '
                . 'Created="2023-10-26T13:06:30+03:00",\ Nonce="eEhwWFozbTVNbWhJMFFBaFNNUE85c3dUK0pvPQ=="'
        ]);
        self::assertFalse($this->wsseAuthenticator->supports($request));
        // absent Created
        $request->headers = new HeaderBag([
            'X-WSSE' => '\ UsernameToken\ Username="admin",\ PasswordDigest="FmPUH4muyTze6PyRAZGGxv5Thag=",\ '
                . 'Nonce="eEhwWFozbTVNbWhJMFFBaFNNUE85c3dUK0pvPQ=="'
        ]);
        self::assertFalse($this->wsseAuthenticator->supports($request));
        // absent PasswordDigest
        $request->headers = new HeaderBag([
            'X-WSSE' => '\ UsernameToken\ Username="admin",\ '
                . 'Created="2023-10-26T13:06:30+03:00",\ Nonce="eEhwWFozbTVNbWhJMFFBaFNNUE85c3dUK0pvPQ=="'
        ]);
        self::assertFalse($this->wsseAuthenticator->supports($request));
    }

    public function testAuthenticateOnCorrectData(): void
    {
        $request = new Request();
        $created = gmdate(DATE_ATOM);
        $request->headers = new HeaderBag([
            'X-WSSE' => '\ UsernameToken\ Username="admin",\ PasswordDigest="FmPUH4muyTze6PyRAZGGxv5Thag=",\ '
                . 'Created="' . $created . '",\ Nonce="eEhwWFozbTVNbWhJMFFBaFNNUE85c3dUK0pvPQ=="'
        ]);
        $user = $this->getActiveUser();
        $this->encoder->expects($this->once())
            ->method('hash')
            ->willReturn(self::TEST_API_KEY);
        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->willReturn($user);

        $passport = $this->wsseAuthenticator->authenticate($request);

        self::assertTrue($passport instanceof SelfValidatingPassport);
        self::assertEquals($user, $passport->getUser());
    }

    /**
     * @dataProvider wrongAuthenticationProvider
     */
    public function testAuthenticateOnWrongData(
        User $user,
        HeaderBag $headerBag,
        string $secret,
        string $exceptionType,
        string $exceptionString,
    ): void {
        $this->markTestSkipped('Will be fixed in BAP-22397');
        $request = new Request();
        $request->headers = $headerBag;

        $this->expectException($exceptionType);
        $this->expectExceptionMessage($exceptionString);

        $this->encoder->expects($this->any())
            ->method('hash')
            ->willReturn(self::TEST_API_KEY);
        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->willReturn($user);

        $this->wsseAuthenticator->authenticate($request);
    }

    public function wrongAuthenticationProvider(): array
    {
        $created = gmdate(DATE_ATOM);
        $wrongFormatedCreate = gmdate(DATE_COOKIE);
        $createInFeature = (new \DateTime())->modify('+10 month')->format(DATE_ATOM);
        $createExpired = (new \DateTime())->modify('-10 month')->format(DATE_ATOM);
        $baseHeader = new HeaderBag([
            'X-WSSE' => '\ UsernameToken\ Username="admin",\ PasswordDigest="FmPUH4muyTze6PyRAZGGxv5Thag=",\ '
                . 'Created="' . $created . '",\ Nonce="eEhwWFozbTVNbWhJMFFBaFNNUE85c3dUK0pvPQ=="'
        ]);
        $userWithEmptyApiKeys = $this->getActiveUser();
        $apiKeys = $userWithEmptyApiKeys->getApiKeys();
        $userWithEmptyApiKeys->removeApiKey($apiKeys[0]);

        return [
            'user with empty API keys' => [
                $userWithEmptyApiKeys,
                $baseHeader,
                self::TEST_API_KEY,
                AuthenticationException::class,
                'WSSE authentication failed.',
            ],
            'user with disabled organization' => [
                $this->getActiveUser(false),
                $baseHeader,
                self::TEST_API_KEY,
                BadUserOrganizationException::class,
                'Organization is not active.',
            ],
            'wrong api key to user organizaton' => [
                $this->getActiveUser(true, false),
                $baseHeader,
                self::TEST_API_KEY,
                BadCredentialsException::class,
                'Wrong API key.',
            ],
            'incorrectly formatted created in token' => [
                $this->getActiveUser(true, false),
                new HeaderBag([
                    'X-WSSE' => '\ UsernameToken\ Username="admin",\ PasswordDigest="FmPUH4muyTze6PyRAZGGxv5Thag=",\ '
                        . 'Created="' . $wrongFormatedCreate . '",\ Nonce="eEhwWFozbTVNbWhJMFFBaFNNUE85c3dUK0pvPQ=="'
                ]),
                self::TEST_API_KEY,
                BadCredentialsException::class,
                'Incorrectly formatted "created" in token.',
            ],
            'future token detected' => [
                $this->getActiveUser(true, false),
                new HeaderBag([
                    'X-WSSE' => '\ UsernameToken\ Username="admin",\ PasswordDigest="FmPUH4muyTze6PyRAZGGxv5Thag=",\ '
                        . 'Created="' . $createInFeature . '",\ Nonce="eEhwWFozbTVNbWhJMFFBaFNNUE85c3dUK0pvPQ=="'
                ]),
                self::TEST_API_KEY,
                BadCredentialsException::class,
                'Future token detected.',
            ],
            'token has expired' => [
                $this->getActiveUser(true, false),
                new HeaderBag([
                    'X-WSSE' => '\ UsernameToken\ Username="admin",\ PasswordDigest="FmPUH4muyTze6PyRAZGGxv5Thag=",\ '
                        . 'Created="' . $createExpired . '",\ Nonce="eEhwWFozbTVNbWhJMFFBaFNNUE85c3dUK0pvPQ=="'
                ]),
                self::TEST_API_KEY,
                CredentialsExpiredException::class,
                'Token has expired.',
            ],
        ];
    }

    public function testAuthenticationSuccess(): void
    {
        $request = new Request();
        $token = new WsseToken($this->getActiveUser(), self::FIREWALL_NAME);
        $result = $this->wsseAuthenticator->onAuthenticationSuccess($request, $token, self::FIREWALL_NAME);

        self::assertSame(null, $result);
    }

    public function testAuthenticationFailure(): void
    {
        $request = new Request();
        $authExeption = new AuthenticationException('WSSE authentication failed.');
        $this->entryPoint
            ->expects($this->once())
            ->method('start')
            ->with($request, $authExeption);
        $result = $this->wsseAuthenticator->onAuthenticationFailure($request, $authExeption);

        self::assertTrue($result instanceof Response);
    }

    public function testCreateToken()
    {
        $created = gmdate(DATE_ATOM);
        $wsseHeader = '\ UsernameToken\ Username="admin",\ PasswordDigest="FmPUH4muyTze6PyRAZGGxv5Thag=",\ '
            . 'Created="' . $created . '",\ Nonce="eEhwWFozbTVNbWhJMFFBaFNNUE85c3dUK0pvPQ=="';
        $wsseHeaderData = $this->getHeaderData($wsseHeader);
        $passport = new SelfValidatingPassport(
            new UserBadge($wsseHeaderData['Username'], [$this->userProvider, 'loadUserByIdentifier']),
            [new RememberMeBadge()]
        );
        $user = $this->getActiveUser();
        $apiKey = $user->getApiKeys()->first();
        $passport->setAttribute('userApiKey', $apiKey);

        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByIdentifier')
            ->willReturn($user);
        $this->wsseTokenFactory->expects($this->once())
            ->method('create')
            ->with($user, self::FIREWALL_NAME, $user->getRoles());

        $wsseToken = $this->wsseAuthenticator->createToken($passport, self::FIREWALL_NAME);
        self::assertTrue($wsseToken instanceof WsseToken);
    }

    private function getActiveUser(bool $isEnabledOrganization = true, bool $isActiveApi = true): User
    {
        $organization = new Organization();
        $organization->setEnabled($isEnabledOrganization);

        $userApiKey = new UserApi();
        $userApiKey->setApiKey(self::TEST_API_KEY);
        $userApiKey->setOrganization($organization);
        if (!$isActiveApi) {
            $organization1 = new Organization();
            $organization1->setEnabled($isEnabledOrganization);
            $userApiKey->setOrganization($organization1);
        }
        $advancedUser = new User();
        $advancedUser->addOrganization($organization);
        $advancedUser->addApiKey($userApiKey);
        $advancedUser->setEnabled(true);
        $advancedUser->setAuthStatus(new TestEnumValue(UserManager::STATUS_ACTIVE, UserManager::STATUS_ACTIVE));
        $role = $this->createMock(Role::class);
        $advancedUser->setUserRoles([$role]);
        $advancedUser->setUsername('sample_user');
        $userApiKey->setUser($advancedUser);

        return $advancedUser;
    }

    private function getHeaderData(string $wsseHeader): array
    {
        $result = [];
        foreach (['Username', 'PasswordDigest', 'Nonce', 'Created'] as $key) {
            if ($value = $this->parseValue($wsseHeader, $key)) {
                $result[$key] = $value;
            }
        }

        return count($result) === 4 ? $result : [];
    }

    private function parseValue(string $wsseHeader, string $key): ?string
    {
        preg_match('/' . $key . '="([^"]+)"/', $wsseHeader, $matches);

        return $matches[1] ?? null;
    }
}
