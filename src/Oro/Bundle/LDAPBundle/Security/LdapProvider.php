<?php

namespace Oro\Bundle\LDAPBundle\Security;

use Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider;
use Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use Oro\Bundle\LDAPBundle\LDAP\LdapChannelManager;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\UserOrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

class LdapProvider extends UserAuthenticationProvider
{
    /** @var UserProviderInterface */
    protected $userProvider;
    /** @var ChannelManagerProvider */
    private $managerProvider;

    /**
     * @param UserCheckerInterface $userChecker
     * @param string $providerKey
     * @param UserProviderInterface $userProvider
     * @param ChannelManagerProvider $managerProvider
     * @param boolean $hideUserNotFoundExceptions
     */
    public function __construct(
        UserCheckerInterface $userChecker,
        $providerKey,
        UserProviderInterface $userProvider,
        ChannelManagerProvider $managerProvider,
        $hideUserNotFoundExceptions = true
    ) {
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);
        $this->userProvider = $userProvider;
        $this->managerProvider = $managerProvider;
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

        // If there were any changes to mapping array ...
        if ($userMapping !== $currentMapping) {
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
        if ('' === ($presentedPassword = $token->getCredentials())) {
            throw new BadCredentialsException('The presented password cannot be empty.');
        }

        if (false === $this->managerProvider->bind($user, $presentedPassword)) {
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
