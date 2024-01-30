<?php

namespace Oro\Bundle\SecurityBundle\Http\Firewall;

use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Basic organization authenticator.
 */
class OrganizationBasicAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private ?UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory = null;

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private string $realmName,
        private UserProviderInterface $userProvider,
        private OrganizationManager $organizationManager,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function setTokenFactory(UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory): void
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function supports(Request $request): ?bool
    {
        $username = $request->headers->get('PHP_AUTH_USER');
        if (null === $username) {
            return false;
        }
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            if ($token instanceof OrganizationAwareTokenInterface
                && null !== $token->getUser()
                && $username === $token->getUser()->getUserIdentifier()
            ) {
                return false;
            }
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->headers->get('PHP_AUTH_USER');
        $this->logProcess($username);

        $passport = new Passport(
            new UserBadge($username, [$this->userProvider, 'loadUserByIdentifier']),
            new PasswordCredentials($request->headers->get('PHP_AUTH_PW')),
        );
        if ($request->headers->has('PHP_AUTH_ORGANIZATION')) {
            $passport->setAttribute(
                'organization',
                $this->organizationManager->getOrganizationById($request->headers->get('PHP_AUTH_ORGANIZATION'))
            );
        }

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        if (null === $this->tokenFactory) {
            throw new AuthenticationException('Token Factory is not set in OrganizationBasicAuthenticator.');
        }
        $organization = $passport->getAttribute('organization');
        $user = $passport->getUser();
        if (null !== $organization) {
            $authToken = $this->tokenFactory
                ->create(
                    $user,
                    $firewallName,
                    $organization,
                    $user->getRoles()
                );
        } else {
            $authToken = new UsernamePasswordToken(
                $user,
                $firewallName,
                $user->getRoles()
            );
        }
        $this->tokenStorage->setToken($authToken);

        return $authToken;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger?->info(
            'Basic authentication failed for user.',
            ['username' => $request->headers->get('PHP_AUTH_USER'), 'exception' => $exception]
        );

        return $this->start($request, $exception);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $response = new Response();
        $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realmName));
        $response->setStatusCode(401);

        return $response;
    }

    protected function logProcess(string $username): void
    {
        $this->logger?->info(
            sprintf('Basic Organization Authentication Authorization header found for user "%s"', $username)
        );
    }
}
