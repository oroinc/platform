<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Event\ImpersonationSuccessEvent;
use Oro\Bundle\UserBundle\Exception\ImpersonationAuthenticationException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authenticator guard for impersonated authentication.
 */
class ImpersonationAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    public const TOKEN_PARAMETER = '_impersonation_token';

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory,
        private OrganizationGuesserInterface $organizationGuesser,
        private EventDispatcherInterface $eventDispatcher,
        private UrlGeneratorInterface $router
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->query->has(static::TOKEN_PARAMETER);
    }

    public function authenticate(Request $request): Passport
    {
        $impersonationToken = $this->getImpersonationToken($request);
        if (null === $impersonationToken) {
            throw new ImpersonationAuthenticationException('Impersonation token is not set.');
        }
        return new SelfValidatingPassport(
            new UserBadge($impersonationToken, [$this, 'getUserByImpersonationToken'])
        );
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        /** @var User $user */
        $user = $passport->getUser();
        $organization = $this->organizationGuesser->guess($user);

        return $this->tokenFactory->create($user, $firewallName, $organization, $user->getUserRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $firewallName): ?Response
    {
        $impersonation = $this->getImpersonation($this->getImpersonationToken($request));
        $token->setAttribute('IMPERSONATION', $impersonation->getId());

        $event = new ImpersonationSuccessEvent($impersonation);
        $this->eventDispatcher->dispatch($event, ImpersonationSuccessEvent::EVENT_NAME);

        $impersonation->setLoginAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $impersonation->setIpAddress($request->getClientIp());
        $this->doctrine->getManager()->flush();

        return null;
    }

    public function getUserByImpersonationToken(string $impersonationToken): AbstractUser
    {
        $impersonation = $this->getImpersonation($impersonationToken);
        $this->checkImpersonation($impersonation);

        return $impersonation->getUser();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate('oro_user_security_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        if ($authException) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $authException);
        }

        return new RedirectResponse($this->router->generate('oro_user_security_login'));
    }

    protected function getImpersonation(string $impersonationToken): ?Impersonation
    {
        return $this->doctrine
            ->getRepository(Impersonation::class)
            ->findOneBy(['token' => $impersonationToken]);
    }

    private function getImpersonationToken(Request $request): ?string
    {
        return $request->query->get(static::TOKEN_PARAMETER);
    }

    private function checkImpersonation(Impersonation $impersonation = null): void
    {
        if (!$impersonation) {
            throw new AuthenticationCredentialsNotFoundException();
        }
        if ($impersonation->getLoginAt()) {
            $exception = new ImpersonationAuthenticationException('Impersonation token has already been used.');
            $exception->setUser($impersonation->getUser());
            throw $exception;
        }
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        if ($impersonation->getExpireAt() <= $now) {
            $exception = new ImpersonationAuthenticationException('Impersonation token has expired.');
            $exception->setUser($impersonation->getUser());
            throw $exception;
        }
    }
}
