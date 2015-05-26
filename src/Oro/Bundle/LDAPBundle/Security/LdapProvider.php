<?php

namespace Oro\Bundle\LDAPBundle\Security;

use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use Oro\Bundle\LDAPBundle\LDAP\Factory\LdapManagerFactory;
use Oro\Bundle\LDAPBundle\Model\User;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

class LdapProvider extends UserAuthenticationProvider
{
    /** @var LdapManagerFactory */
    protected $ldapManagerFactory;

    /** @var UserProviderInterface */
    protected $userProvider;

    /**
     * @param UserCheckerInterface $userChecker
     * @param string $providerKey
     * @param UserProviderInterface $userProvider
     * @param LdapManagerFactory $ldapManagerFactory
     * @param boolean $hideUserNotFoundExceptions
     */
    public function __construct(
        UserCheckerInterface $userChecker,
        $providerKey,
        UserProviderInterface $userProvider,
        LdapManagerFactory $ldapManagerFactory,
        $hideUserNotFoundExceptions = true
    ) {
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);
        $this->ldapManagerFactory = $ldapManagerFactory;
        $this->userProvider = $userProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        $guesser = new UserOrganizationGuesser();
        $parentAuthenticatedToken = parent::authenticate($token);

        $user = $parentAuthenticatedToken->getUser();
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
            $this->checkAuthAgainstCurrentUser($user, $currentUser);
        } else {
            $this->checkAuthAgainstLdap($user, $token);
        }
    }

    /**
     * Checks authentication against current user.
     *
     * @param UserInterface $user
     * @param UserInterface $currentUser
     *
     * @throws BadCredentialsException
     */
    protected function checkAuthAgainstCurrentUser(UserInterface $user, UserInterface $currentUser)
    {
        // Get mappings for users
        $userMapping = (array)$user->getLdapMappings();
        $currentMapping = (array)$currentUser->getLdapMappings();

        // If any mapping is empty, there is nothing to authenticate against.
        if (empty($userMapping) || empty($currentMapping)) {
            throw new BadCredentialsException('There is no LDAP Integration to authenticate against.');
        }

        $commonMapping = array_intersect_key($userMapping, $currentMapping);
        if (empty($commonMapping)) {
            throw new BadCredentialsException('The credentials were changed from another session.');
        }

        $matched = false;
        foreach ($commonMapping as $mapping) {
            if ($userMapping[$mapping] == $currentMapping[$mapping]) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            throw new BadCredentialsException('The credentials were changed from another session.');
        }
    }

    /**
     * Checks authentication against LDAP.
     *
     * @param UserInterface $user
     * @param UsernamePasswordToken $token
     *
     * @throws BadCredentialsException
     */
    protected function checkAuthAgainstLdap(UserInterface $user, UsernamePasswordToken $token)
    {
        $mappings = (array)$user->getLdapMappings();
        if (empty($mappings)) {
            throw new BadCredentialsException('There is no LDAP Integration to authenticate against.');
        }

        if ('' === ($presentedPassword = $token->getCredentials())) {
            throw new BadCredentialsException('The presented password cannot be empty.');
        }

        $ldapUser = User::createFromUser($user);

        $bound = false;
        foreach ($mappings as $channelId) {
            $manager = $this->ldapManagerFactory->getInstanceForChannelId($channelId);
            if ($manager->bind($ldapUser, $presentedPassword)) {
                $bound = true;
                break;
            }
        }

        if (!$bound) {
            throw new BadCredentialsException('The presented password is invalid.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        $user = $token->getUser();
        if ($user instanceof UserInterface) {
            return $user;
        }

        try {
            $user = $this->userProvider->loadUserByUsername($username);

            return $user;
        } catch (UsernameNotFoundException $notFound) {
            throw $notFound;
        } catch (\Exception $repositoryProblem) {
            $e = new AuthenticationServiceException(
                $repositoryProblem->getMessage(),
                (int) $repositoryProblem->getCode(),
                $repositoryProblem
            );
            $e->setToken($token);

            throw $e;
        }
    }
}
