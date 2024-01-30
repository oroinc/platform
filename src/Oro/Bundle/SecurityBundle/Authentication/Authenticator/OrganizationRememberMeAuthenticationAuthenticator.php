<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Authentication\Authenticator;

use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\RememberMeAuthenticator;
use Symfony\Component\Security\Http\RememberMe\RememberMeHandlerInterface;

/**
 * Authenticator that authenticates a user based on a remember-me cookie.
 */
class OrganizationRememberMeAuthenticationAuthenticator extends RememberMeAuthenticator
{
    private string $secret;
    private OrganizationRememberMeTokenFactoryInterface $tokenFactory;
    private OrganizationGuesserInterface $organizationGuesser;

    public function __construct(
        RememberMeHandlerInterface $rememberMeHandler,
        string $secret,
        TokenStorageInterface $tokenStorage,
        string $cookieName,
        LoggerInterface $logger = null
    ) {
        parent::__construct($rememberMeHandler, $secret, $tokenStorage, $cookieName, $logger);
        $this->secret = $secret;
    }

    public function setTokenFactory(OrganizationRememberMeTokenFactoryInterface $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;
    }

    public function setOrganizationGuesser(OrganizationGuesserInterface $organizationGuesser)
    {
        $this->organizationGuesser = $organizationGuesser;
    }

    public function authenticate(Request $request): Passport
    {
        $passport = parent::authenticate($request);
        $user = $passport->getUser();
        $organization = $this->organizationGuesser->guess($user);
        $passport->setAttribute('organization', $organization);

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return $this->tokenFactory->create(
            $passport->getUser(),
            $firewallName,
            $this->secret,
            $passport->getAttribute('organization')
        );
    }
}
