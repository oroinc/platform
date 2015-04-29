<?php

namespace Oro\Bundle\LDAPBundle\Security;

use FR3D\LdapBundle\Ldap\LdapManagerInterface;
use FR3D\LdapBundle\Security\Authentication\LdapAuthenticationProvider;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LDAPBundle\Model\User;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

class LdapProvider extends LdapAuthenticationProvider
{
    /** @var LdapManagerInterface */
    protected $ldapManager;

    /** @var ConfigManager */
    protected $cm;

    /**
     * @param UserCheckerInterface  $userChecker
     * @param string                $providerKey
     * @param UserProviderInterface $userProvider
     * @param LdapManagerInterface  $ldapManager
     * @param ConfigManager         $cm
     * @param boolean               $hideUserNotFoundExceptions
     */
    public function __construct(
        UserCheckerInterface $userChecker,
        $providerKey,
        UserProviderInterface $userProvider,
        LdapManagerInterface $ldapManager,
        ConfigManager $cm,
        $hideUserNotFoundExceptions = true
    ) {
        parent::__construct($userChecker, $providerKey, $userProvider, $ldapManager, $hideUserNotFoundExceptions);
        $this->ldapManager = $ldapManager;
        $this->cm          = $cm;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->cm->get('oro_ldap.server_enable_login')) {
            throw new BadCredentialsException('Bad credentials');
        }

        $guesser = new UserOrganizationGuesser();
        $parentAuthenticatedToken = parent::authenticate($token);

        $user         = $parentAuthenticatedToken->getUser();
        $organization = $guesser->guess($user, $token);

        if (!$organization) {
            throw new BadCredentialsException("You don't have active organization assigned.");
        } elseif (!$user->getOrganizations(true)->contains($organization)) {
            throw new BadCredentialsException(
                sprintf("You don't have access to organization '%s'", $organization->getName())
            );
        }

        $authenticatedToken = new UsernamePasswordOrganizationToken(
            $parentAuthenticatedToken->getUser(),
            $parentAuthenticatedToken->getCredentials(),
            $parentAuthenticatedToken->getProviderKey(),
            $organization,
            $parentAuthenticatedToken->getRoles()
        );

        return $authenticatedToken;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $currentUser = $token->getUser();
        if ($currentUser instanceof UserInterface) {
            if ($user->getDn() !== $currentUser->getDn()) {
                throw new BadCredentialsException('The credentials were changed from another session.');
            }
        } else {
            if ('' === ($presentedPassword = $token->getCredentials())) {
                throw new BadCredentialsException('The presented password cannot be empty.');
            }

            $ldapUser = User::createFromUser($user);
            if (!$this->ldapManager->bind($ldapUser, $presentedPassword)) {
                throw new BadCredentialsException('The presented password is invalid.');
            }
        }
    }
}
