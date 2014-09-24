<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\RememberMeAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

use Oro\Bundle\UserBundle\Entity\User;

class OrganizationRememberMeAuthenticationProvider extends RememberMeAuthenticationProvider
{
    /**
     * @var string
     */
    protected $providerKey;

    /**
     * Constructor.
     *
     * @param UserCheckerInterface $userChecker An UserCheckerInterface interface
     * @param string               $key         A key
     * @param string               $providerKey A provider key
     */
    public function __construct(UserCheckerInterface $userChecker, $key, $providerKey)
    {
        parent::__construct($userChecker, $key, $providerKey);
        $this->providerKey = $providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        /**  @var OrganizationContextTokenInterface $token */
        $rememberMeToken = parent::authenticate($token);

        $this->checkUserOrganization($rememberMeToken->getUser(), $token->getOrganizationContext());

        $authenticatedToken = new OrganizationRememberMeToken(
            $rememberMeToken->getUser(),
            $rememberMeToken->getProviderKey(),
            $rememberMeToken->getKey(),
            $token->getOrganizationContext()
        );

        return $authenticatedToken;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OrganizationRememberMeToken && $this->providerKey === $token->getProviderKey();
    }

    /**
     * @param User         $user
     * @param Organization $organization
     * @throws BadCredentialsException
     */
    protected function checkUserOrganization(User $user, Organization $organization)
    {
        if (!$user->getOrganizations()->contains($organization) || !$organization->isEnabled()) {
            throw new BadCredentialsException(
                sprintf("You don't have access to organization '%s'", $organization->getName())
            );
        }
    }
}
