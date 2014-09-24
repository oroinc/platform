<?php

namespace Oro\Bundle\SecurityBundle\Http\RememberMe;

use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CookieTheftException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken;

/**
 * Class OrganizationTokenBasedRememberMeServices
 * We could not extend Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices class because
 * parent Symfony\Component\Security\Http\RememberMe\AbstractRememberMeServices class has final autoLogin function
 * which we must rewrite.
 *
 * This class should be refactored after autoLogin function will set to not final.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrganizationTokenBasedRememberMeServices implements RememberMeServicesInterface, LogoutHandlerInterface
{
    const COOKIE_DELIMITER = ':';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $providerKey;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var array
     */
    protected $userProviders;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param array           $userProviders
     * @param string          $key
     * @param string          $providerKey
     * @param array           $options
     * @param LoggerInterface $logger
     */
    public function __construct(
        array $userProviders,
        $key,
        $providerKey,
        array $options = array(),
        LoggerInterface $logger = null
    ) {
        $this->userProviders = $userProviders;
        $this->key = $key;
        $this->providerKey = $providerKey;
        $this->options = $options;
        $this->logger = $logger;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $this->cancelCookie($request);
    }

    /**
     * {@inheritdoc}
     */
    public function autoLogin(Request $request)
    {
        $cookie = $request->cookies->get($this->options['name']);
        if (null === $cookie) {
            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug('Organization Remember-me cookie detected.');
        }

        $cookieParts = explode(self::COOKIE_DELIMITER, base64_decode($cookie));

        try {
            list($user, $organization) = $this->getUserAndOrganizationFromCookie($cookieParts, $request);

            if (null !== $this->logger) {
                $this->logger->info('Remember-me cookie accepted.');
            }

            return new OrganizationRememberMeToken($user, $this->providerKey, $this->key, $organization);
        } catch (CookieTheftException $theft) {
            $this->cancelCookie($request);

            throw $theft;
        } catch (UsernameNotFoundException $notFound) {
            if (null !== $this->logger) {
                $this->logger->info('User for organization-remember-me cookie not found.');
            }
        } catch (UnsupportedUserException $unSupported) {
            if (null !== $this->logger) {
                $this->logger->warning('User class for organization-remember-me cookie not supported.');
            }
        } catch (AuthenticationException $invalid) {
            if (null !== $this->logger) {
                $this->logger->debug('Organization Remember-Me authentication failed: ' . $invalid->getMessage());
            }
        }

        $this->cancelCookie($request);
    }

    /**
     * {@inheritdoc}
     */
    public function loginFail(Request $request)
    {
        $this->cancelCookie($request);
    }

    /**
     * {@inheritdoc}
     */
    public function loginSuccess(Request $request, Response $response, TokenInterface $token)
    {
        // Make sure any old remember-me cookies are cancelled
        $this->cancelCookie($request);

        if (!$token->getUser() instanceof UserInterface) {
            if (null !== $this->logger) {
                $this->logger->debug(
                    'Organization Remember-me ignores token since it does not contain a UserInterface implementation.'
                );
            }

            return;
        }

        if (!$this->isRememberMeRequested($request)) {
            if (null !== $this->logger) {
                $this->logger->debug('Organization Remember-me was not requested.');
            }

            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug('Organization Remember-me was requested; setting cookie.');
        }

        $request->attributes->remove(self::COOKIE_ATTR_NAME);

        $user = $token->getUser();
        $expires = time() + $this->options['lifetime'];
        $value = $this->generateCookieValue(
            get_class($user),
            $user->getUsername(),
            $expires,
            $user->getPassword(),
            $token->getOrganizationContext()->getId()
        );

        $response->headers->setCookie(
            new Cookie(
                $this->options['name'],
                $value,
                $expires,
                $this->options['path'],
                $this->options['domain'],
                $this->options['secure'],
                $this->options['httponly']
            )
        );
    }

    /**
     * @param string $class
     */
    protected function getUserProvider($class)
    {
        foreach ($this->userProviders as $provider) {
            if ($provider->supportsClass($class)) {

                return $provider;
            }
        }

        throw new UnsupportedUserException(sprintf('There is no user provider that supports class "%s".', $class));
    }

    /**
     * @param array $cookieParts
     * @return array
     */
    protected function getUserAndOrganizationFromCookie($cookieParts)
    {
        if (count($cookieParts) !== 5) {
            throw new AuthenticationException('The cookie is invalid.');
        }

        list($class, $username, $expires, $hash, $organizationId) = $cookieParts;
        if (false === $username = base64_decode($username, true)) {
            throw new AuthenticationException('$username contains a character from outside the base64 alphabet.');
        }
        try {
            $organization = $this->entityManager
                ->getRepository('OroOrganizationBundle:Organization')
                ->find($organizationId);
            $user = $this->getUserProvider($class)->loadUserByUsername($username);
        } catch (\Exception $ex) {
            if (!$ex instanceof AuthenticationException) {
                $ex = new AuthenticationException($ex->getMessage(), $ex->getCode(), $ex);
            }

            throw $ex;
        }

        $this->checkUserData($user, $organization, $class, $username, $organizationId, $expires, $hash);

        return [$user, $organization];
    }

    /**
     * Check
     * @param User         $user
     * @param Organization $organization
     * @param string       $class
     * @param string       $username
     * @param int          $organizationId
     * @param int          $expires
     * @param string       $hash
     */
    protected function checkUserData(
        User $user,
        Organization $organization,
        $class,
        $username,
        $organizationId,
        $expires,
        $hash
    ) {
        if (!$user instanceof UserInterface) {
            throw new \RuntimeException(
                sprintf(
                    'The UserProviderInterface implementation must return an instance of UserInterface,
                     but returned "%s".',
                    get_class($user)
                )
            );
        }

        if (!$organization instanceof Organization) {
            throw new \RuntimeException(sprintf('Can not find organization with id "%s".', $organizationId));
        }

        if (!$organization->isEnabled()) {
            throw new \RuntimeException(sprintf('Organization "%s" is not active.', $organization->getName()));
        }

        if (!$user->getOrganizations()->contains($organization)) {
            throw new AuthenticationException(
                sprintf(
                    'User "%s" does not have access to organization "%s".',
                    $username,
                    $organization->getName()
                )
            );
        }

        $isHashesIdentical = $this->compareHashes(
            $hash,
            $this->generateCookieHash($class, $username, $expires, $user->getPassword())
        );
        if (true !== $isHashesIdentical) {
            throw new AuthenticationException('The cookie\'s hash is invalid.');
        }

        if ($expires < time()) {
            throw new AuthenticationException('The cookie has expired.');
        }
    }

    /**
     * Compares two hashes using a constant-time algorithm to avoid (remote)
     * timing attacks.
     *
     * This is the same implementation as used in the BasePasswordEncoder.
     *
     * @param string $hash1 The first hash
     * @param string $hash2 The second hash
     *
     * @return bool    true if the two hashes are the same, false otherwise
     */
    protected function compareHashes($hash1, $hash2)
    {
        if (strlen($hash1) !== $c = strlen($hash2)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $c; $i++) {
            $result |= ord($hash1[$i]) ^ ord($hash2[$i]);
        }

        return 0 === $result;
    }

    /**
     * Generates the cookie value.
     *
     * @param string $class
     * @param string $username       The username
     * @param int    $expires        The Unix timestamp when the cookie expires
     * @param string $password       The encoded password
     * @param int    $organizationId Organization context id
     *
     * @throws \RuntimeException if username contains invalid chars
     *
     * @return string
     */
    protected function generateCookieValue($class, $username, $expires, $password, $organizationId)
    {
        return base64_encode(
            implode(
                self::COOKIE_DELIMITER,
                [
                    $class,
                    base64_encode($username),
                    $expires,
                    $this->generateCookieHash($class, $username, $expires, $password),
                    $organizationId
                ]
            )
        );
    }

    /**
     * Generates a hash for the cookie to ensure it is not being tempered with
     *
     * @param string $class
     * @param string $username The username
     * @param int    $expires  The Unix timestamp when the cookie expires
     * @param string $password The encoded password
     *
     * @throws \RuntimeException when the private key is empty
     *
     * @return string
     */
    protected function generateCookieHash($class, $username, $expires, $password)
    {
        return hash('sha256', $class . $username . $expires . $password . $this->key);
    }

    /**
     * Deletes the remember-me cookie
     *
     * @param Request $request
     */
    protected function cancelCookie(Request $request)
    {
        if (null !== $this->logger) {
            $this->logger->debug(sprintf('Clearing Organization remember-me cookie "%s"', $this->options['name']));
        }

        $request->attributes->set(
            self::COOKIE_ATTR_NAME,
            new Cookie($this->options['name'], null, 1, $this->options['path'], $this->options['domain'])
        );
    }

    /**
     * Checks whether remember-me capabilities were requested
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function isRememberMeRequested(Request $request)
    {
        if (true === $this->options['always_remember_me']) {
            return true;
        }

        $parameter = $request->get($this->options['remember_me_parameter'], null, true);

        if (null === $parameter && null !== $this->logger) {
            $this->logger->debug(
                sprintf(
                    'Did not send remember-me cookie (remember-me parameter "%s" was not sent).',
                    $this->options['remember_me_parameter']
                )
            );
        }

        return $parameter === 'true' || $parameter === 'on' || $parameter === '1' || $parameter === 'yes';
    }
}
