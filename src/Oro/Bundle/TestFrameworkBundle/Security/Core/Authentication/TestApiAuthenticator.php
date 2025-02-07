<?php

namespace Oro\Bundle\TestFrameworkBundle\Security\Core\Authentication;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Security\FeatureDependAuthenticatorChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Bundle\TestFrameworkBundle\Security\TestApiToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Authenticator that can be used in functional tests to authenticate with the X-API-TEST header.
 */
class TestApiAuthenticator implements AuthenticatorInterface
{
    private UserProviderInterface $userProvider;
    private string $firewallName;

    public function __construct(
        private OrganizationGuesserInterface $organizationGuesser,
        private UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory,
        private ManagerRegistry $doctrine,
        private FeatureDependAuthenticatorChecker $featureDependAuthenticatorChecker,
    ) {
    }

    public function setUserProvider(UserProviderInterface $userProvider): void
    {
        $this->userProvider = $userProvider;
    }

    public function setFirewallName(string $firewallName): void
    {
        $this->firewallName = $firewallName;
    }

    #[\Override]
    public function supports(Request $request): ?bool
    {
        if (!$this->featureDependAuthenticatorChecker->isEnabled($this, $this->firewallName)) {
            return false;
        }

        return $request->headers->has('X-API-TEST');
    }

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $headerData = $this->getHeaderData((string)$request->headers->get('X-API-TEST'));
        $passport = new SelfValidatingPassport(
            new UserBadge($headerData['username'], $this->userProvider->loadUserByIdentifier(...))
        );
        $passport->setAttribute('organization', (int)$headerData['organization']);

        return $passport;
    }

    #[\Override]
    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $user = $passport->getUser();
        $organization = $passport->getAttribute('organization')
            ? $this->doctrine->getRepository(Organization::class)->find($passport->getAttribute('organization'))
            : $this->organizationGuesser->guess($user);

        if (!$organization) {
            throw new AuthenticationException();
        }

        $token = new TestApiToken($user, $firewallName, $user->getRoles());
        $token->setOrganization($organization);

        return $token;
    }

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    #[\Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response('', 401, ['WWW-Authenticate' => 'TEST_API']);
    }


    private function getHeaderData(string $headerData): array
    {
        $headerData = explode('^', $headerData);
        return ['username' => $headerData[0], 'organization' => $headerData[1] ?? null];
    }
}
